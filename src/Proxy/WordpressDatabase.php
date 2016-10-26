<?php
namespace Koselig\Proxy;

use Cache;
use DB;
use Illuminate\Database\QueryException;
use wpdb;

/**
 * Replace Wordpress' database calls to Laravel's database abstraction
 * to hold a single database connection, and for easier query debugging.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressDatabase extends wpdb
{
    /**
     * Override the constructor as the Wordpress install doesn't actually
     * need our Database details since it's all handled by laravel.
     */
    public function __construct()
    {
        parent::__construct(null, null, null, null);
    }

    /**
     * Override the Wordpress select method as Laravel has already
     * done this for us.
     *
     * @param string $db
     * @param null $dbh
     */
    public function select($db, $dbh = null)
    {
        // We don't need to select a table, Laravel has done it for us.
    }

    /**
     * Laravel handles all the connection handling for us including reconnecting
     * so we'll just pretend we're always connected to whatever is calling us.
     *
     * @param bool $allow_bail
     * @return bool
     */
    public function check_connection($allow_bail = true)
    {
        return true;
    }

    /**
     * Set the properties Wordpress expects so it will run queries for us
     * through this class.
     *
     * @param bool $allow_bail
     *
     * @return void
     */
    public function db_connect($allow_bail = true)
    {
        $this->is_mysql = true;
        $this->has_connected = true;

        $this->ready = true;
        $this->set_sql_mode();
        $this->init_charset();
    }

    /**
     * Retrieves the MySQL server version.
     *
     * @return null|string Null on failure, version number on success.
     */
    public function db_version()
    {
        return Cache::remember('sql_version', config('wordpress.caching'), function () {
            return DB::selectOne('SELECT version() as v')->v;
        });
    }

    /**
     * Change the current SQL mode, and ensure its WordPress compatibility.
     *
     * If no modes are passed, it will ensure the current MySQL server
     * modes are compatible.
     *
     * @since 3.9.0
     *
     * @param array $modes Optional. A list of SQL modes to set.
     */
    public function set_sql_mode($modes = [])
    {
        if (empty($modes)) {
            $modes = Cache::remember('sql_modes', config('wordpress.caching'), function () {
                return explode(',', DB::selectOne('SELECT @@SESSION.sql_mode as sql_mode')->sql_mode);
            });
        }

        $modes = array_change_key_case($modes, CASE_UPPER);

        /**
         * Filters the list of incompatible SQL modes to exclude.
         *
         * @since 3.9.0
         *
         * @param array $incompatible_modes An array of incompatible modes.
         */
        $incompatible_modes = (array) apply_filters('incompatible_sql_modes', $this->incompatible_modes);

        foreach ($modes as $i => $mode) {
            if (in_array($mode, $incompatible_modes, true)) {
                unset($modes[$i]);
            }
        }

        DB::statement('SET SESSION sql_mode=?', [implode(',', $modes)]);
    }

    /**
     * Real escape, using PDO's quote function.
     *
     * TODO: eventually swap this out for proper prepared
     * statements rather than working around Wordpress' limitations.
     *
     * @param  string $string to escape
     *
     * @return string escaped
     */
    public function _real_escape($string)
    {
        return substr(DB::getPdo()->quote($string), 1, -1);
    }

    /**
     * Perform a MySQL database query, using current database connection.
     *
     * More information can be found on the codex page.
     *
     * @since 0.71
     *
     * @param string $query Database query
     *
     * @return false|int Number of rows affected/selected or false on error
     */
    public function query($query)
    {
        if (!$this->ready) {
            $this->check_current_query = true;

            return false;
        }

        /**
         * Filters the database query.
         *
         * Some queries are made before the plugins have been loaded,
         * and thus cannot be filtered with this method.
         *
         * @since 2.1.0
         *
         * @param string $query Database query.
         */
        $query = apply_filters('query', $query);

        $this->flush();

        // Log how the function was called
        $this->func_call = "\$db->query(\"$query\")";

        // If we're writing to the database, make sure the query will write safely.
        if ($this->check_current_query && !$this->check_ascii($query)) {
            $stripped_query = $this->strip_invalid_text_from_query($query);
            // strip_invalid_text_from_query() can perform queries, so we need
            // to flush again, just to make sure everything is clear.
            $this->flush();
            if ($stripped_query !== $query) {
                $this->insert_id = 0;

                return false;
            }
        }

        $this->check_current_query = true;

        // Keep track of the last query for debug.
        $this->last_query = $query;

        try {
            $this->_do_query($query);
        } catch (QueryException $e) {
            $this->last_error = $e->getMessage();

            // Clear insert_id on a subsequent failed insert.
            if ($this->insert_id && preg_match('/^\s*(insert|replace)\s/i', $query)) {
                $this->insert_id = 0;
            }

            $this->print_error($this->last_error);

            return false;
        }

        if (preg_match('/^\s*(create|alter|truncate|drop)\s/i', $query)) {
            $return_val = $this->result;
        } elseif (preg_match('/^\s*(insert|delete|update|replace)\s/i', $query)) {
            $this->rows_affected = $return_val = $this->result;
        } else {
            // Log number of rows the query returned
            // and return number of rows selected
            $this->num_rows = count($this->result);
            $return_val = $this->result;
        }

        return $return_val;
    }

    /**
     * Internal function to perform the mysql_query() call.
     *
     * @since 3.9.0
     * @see wpdb::query()
     *
     * @param string $query The query to run.
     */
    private function _do_query($query)
    {
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $this->timer_start();
        }

        if (preg_match('/^\s*(insert|create|alter|truncate|drop|set)\s/i', $query)) {
            $this->last_result = $this->result = DB::statement($query);
        } elseif (preg_match('/^\s*(delete|update|replace)\s/i', $query)) {
            $this->last_result = $this->result = DB::affectingStatement($query);
        } else {
            if (!config('wordpress.caching')) {
                // remove cached query if caching has been disabled
                Cache::forget('q_' . $query);
            }

            $this->result = Cache::remember('q_' . $query, config('wordpress.caching'), function () use ($query) {
                return DB::select($query);
            });

            $this->last_result = $this->result;
        }

        $this->num_queries++;

        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $this->queries[] = [$query, $this->timer_stop(), $this->get_caller()];
        }
    }
}
