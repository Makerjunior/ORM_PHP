<?php

/**
 * Simple ORM base class refactored for PostgreSQL using PDO.
 * * @abstract
 * @package    SimpleOrm
 * @author     Alex Joyce <im@alex-joyce.com>
 * @editor     Refactored for PostgreSQL/PDO
 */
abstract class SimpleOrm
{
    protected static
        $conn, // PDO connection instance
        $database,
        $pk = 'id';

    private
        $reflectionObject,
        $loadMethod,
        $loadData,
        $modifiedFields = array(),
        $isNew = false;

    protected
        $parentObject,
        $ignoreKeyOnUpdate = true,
        $ignoreKeyOnInsert = true;
        
    /**
     * ER Fine Tuning
     */
    const
        FILTER_IN_PREFIX = 'filterIn',
        FILTER_OUT_PREFIX = 'filterOut';

    /**
     * Loading options.
     */
    const
        LOAD_BY_PK = 1,
        LOAD_BY_ARRAY = 2,
        LOAD_NEW = 3,
        LOAD_EMPTY = 4;

    /**
     * Constructor.
     * * @access public
     * @param mixed $data
     * @param integer $method
     * @return void
     */
    final public function __construct ($data = null, $method = self::LOAD_EMPTY)
    {
        // store raw data
        $this->loadData = $data;
        $this->loadMethod = $method;

        // load our data
        switch ($method)
        {
            case self::LOAD_BY_PK:
                $this->loadByPK();
                break;

            case self::LOAD_BY_ARRAY:
                $this->loadByArray();
                break;

            case self::LOAD_NEW:
                $this->loadByArray();
                $this->insert();
                break;

            case self::LOAD_EMPTY:
                $this->hydrateEmpty();
                break;
        }

        $this->initialise();
    }

    /**
     * Give the class a connection to play with.
     * * @access public
     * @static
     * @param \PDO $conn PDO connection instance.
     * @param string $database (Note: For PDO/Postgres, often this is null/schema name, 
     * but we keep it for compatibility with the original structure).
     * @return void
     */
    public static function useConnection (\PDO $conn, $database)
    {
        self::$conn = $conn;
        self::$database = $database;
        
        // PDO doesn't have select_db like mysqli, connection usually specifies the database
        // We set PDO error mode for easier debugging/exception handling
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); 
    }

    /**
     * Get our connection instance.
     * * @access public
     * @static
     * @return \PDO
     */
    public static function getConnection ()
    {
        if (self::$conn === null) {
            throw new \Exception("Database connection not set. Call SimpleOrm::useConnection() first.");
        }
        return self::$conn;
    }

    /**
     * Get load method.
     *
     * @access public
     * @return integer
     */
    public function getLoadMethod ()
    {
        return $this->loadMethod;
    }

    /**
     * Get load data (raw).
     *
     * @access public
     * @return array
     */
    public function getLoadData ()
    {
        return $this->loadData;
    }

    /**
     * Load ER by Primary Key
     * * @access private
     * @return void
     */
    private function loadByPK ()
    {
        // populate PK
        $this->{self::getTablePk()} = $this->loadData;

        // load data
        $this->hydrateFromDatabase();
    }

    /**
     * Load ER by array hydration.
     * * @access private
     * @return void
     */
    private function loadByArray ()
    {
        // set our data
        foreach ($this->loadData AS $key => $value)
            $this->{$key} = $value;

        // extract columns
        $this->executeOutputFilters();
    }

    /**
     * Hydrate the object with null values.
     * Fetches column names using DESCRIBE (or similar for Postgres).
     * * @access private
     * @return void
     */
    private function hydrateEmpty ()
    {
        // set our data
        if (isset($this->erLoadData) && is_array($this->erLoadData))
            foreach ($this->erLoadData AS $key => $value)
                $this->{$key} = $value;

        foreach ($this->getColumnNames() AS $field)
            $this->{$field} = null;

        // mark object as new
        $this->isNew = true;
    }

    /**
     * Fetch the data from the database.
     * * @access private
     * @throws \Exception If the record is not found.
     * @return void
     */
    private function hydrateFromDatabase ()
    {
        $conn = self::getConnection();
        // Postgres uses double quotes for identifiers and doesn't usually need the database name in the query.
        // We'll use a single table name and assume the connection is on the correct database/schema.
        $sql = sprintf('SELECT * FROM "%s" WHERE "%s" = ?;', self::getTableName(), self::getTablePk());
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$this->id()]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row)
            throw new \Exception(sprintf("%s record not found in database. (PK: %s)", get_called_class(), $this->id()), 2);

        foreach ($row AS $key => $value)
            $this->{$key} = $value;

        // extract columns
        $this->executeOutputFilters();
    }

    /**
     * Get the database name for this ER class.
     * * @access public
     * @static
     * @return string
     */
    public static function getDatabaseName ()
    {
        $className = get_called_class();
        
        return $className::$database;
    }

    /**
     * Get the table name for this ER class.
     * * @access public
     * @static
     * @return string
     */
    public static function getTableName ()
    {
        $className = get_called_class();

        // static prop config
        if (isset($className::$table))
            return $className::$table;

        // assumed config
        return strtolower($className);
    }

    /**
     * Get the PK field name for this ER class.
     * * @access public
     * @static
     * @return string
     */
    public static function getTablePk ()
    {
        $className = get_called_class();

        return $className::$pk;
    }
    
    /**
     * Return the PK for this record.
     * * @access public
     * @return integer
     */
    public function id ()
    {
        return $this->{self::getTablePk()};
    }

    /**
     * Check if the current record has just been created in this instance.
     * * @access public
     * @return boolean
     */
    public function isNew ()
    {
        return $this->isNew;
    }

    /**
     * Executed just before any new records are created.
     * Place holder for sub-classes.
     * * @access public
     * @return void
     */
    public function preInsert ()
    {
    }

    /**
     * Executed just after any new records are created.
     * Place holder for sub-classes.
     * * @access public
     * @return void
     */
    public function postInsert ()
    {
    }

    /**
     * Executed just after the record has loaded.
     * Place holder for sub-classes.
     * * @access public
     * @return void
     */
    public function initialise ()
    {
    }

    /**
     * Execute these filters when loading data from the database.
     * * @access private
     * @return void
     */
    private function executeOutputFilters ()
    {
        $r = new \ReflectionClass(get_class($this));
    
        foreach ($r->getMethods() AS $method)
            if (substr($method->name, 0, strlen(self::FILTER_OUT_PREFIX)) == self::FILTER_OUT_PREFIX)
                $this->{$method->name}();
    }

    /**
     * Execute these filters when saving data to the database.
     * * @access private
     * @return void
     */
    private function executeInputFilters ($array)
    {
        $r = new \ReflectionClass(get_class($this));
    
        foreach ($r->getMethods() AS $method)
            if (substr($method->name, 0, strlen(self::FILTER_IN_PREFIX)) == self::FILTER_IN_PREFIX)
                $array = $this->{$method->name}($array);

        return $array;
    }

    /**
     * Save (insert/update) to the database.
     *
     * @access public
     * @return void
     */
    public function save ()
    {
        if ($this->isNew())
            $this->insert();
        else
            $this->update();
    }

    /**
     * Insert the record.
     *
     * @access private
     * @throws \Exception
     * @return void
     */
    private function insert ()
    {
        $conn = self::getConnection();
        $pk = self::getTablePk();
        $tableName = self::getTableName();
        
        $array = $this->get();

        // run pre inserts
        $this->preInsert($array);

        // input filters
        $array = $this->executeInputFilters($array);

        // remove data not relevant
        $array = array_intersect_key($array, array_flip($this->getColumnNames()));

        // to PK or not to PK
        if ($this->ignoreKeyOnInsert === true)
            unset($array[$pk]);

        // compile statement
        $fieldNames = $fieldMarkers = $values = array();

        foreach ($array AS $key => $value)
        {
            $fieldNames[] = sprintf('"%s"', $key); // Use double quotes for Postgres identifier
            $fieldMarkers[] = '?'; // Use '?' placeholder for PDO
            $values[] = $value;
        }

        // build sql statement (Postgres uses RETURNING for last inserted ID)
        $sql = sprintf('INSERT INTO "%s" (%s) VALUES (%s) RETURNING "%s"', 
            $tableName, 
            implode(', ', $fieldNames), 
            implode(', ', $fieldMarkers), 
            $pk // Column to return
        );
        
        // prepare & execute
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($values); // Pass array of values directly to execute
        } catch (\PDOException $e) {
             throw new \Exception($e->getMessage()."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }

        // set our PK (Postgres returns the value in the result set)
        $newPk = $stmt->fetchColumn(0); // Fetch the returned PK
        if ($newPk) {
            $this->{$pk} = $newPk;
        }

        // mark as old
        $this->isNew = false;
        
        // hydrate (reload from database using the new PK)
        // We use hydrateFromDatabase() to ensure the object is fully loaded with any default values/triggers etc.
        $this->hydrateFromDatabase();

        // run post inserts
        $this->postInsert();
    }

    /**
     * Update the record.
     * * @access public
     * @throws \Exception
     * @return void
     */
    public function update ()
    {
        if ($this->isNew())
            throw new \Exception('Unable to update object, record is new.');
        
        $conn = self::getConnection();
        $pk = self::getTablePk();
        $id = $this->id();

        // input filters
        $array = $this->executeInputFilters($this->get());

        // remove data not relevant
        $array = array_intersect_key($array, array_flip($this->getColumnNames()));

        // to PK or not to PK
        if ($this->ignoreKeyOnUpdate === true)
            unset($array[$pk]);

        // compile statement
        $fields = $values = array();

        foreach ($array AS $key => $value)
        {
            $fields[] = sprintf('"%s" = ?', $key); // Use double quotes for Postgres identifier
            $values[] = $value;
        }

        // where condition value
        $values[] = $id;

        // build sql statement (Use double quotes for Postgres identifiers)
        $sql = sprintf('UPDATE "%s" SET %s WHERE "%s" = ?', 
            self::getTableName(), 
            implode(', ', $fields), 
            $pk
        );

        // prepare & execute
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($values); // Pass array of values directly to execute
        } catch (\PDOException $e) {
             throw new \Exception($e->getMessage()."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }

        // reset modified list
        $this->modifiedFields = array();
    }

    /**
     * Delete the record from the database.
     * * @access public
     * @return void
     */
    public function delete ()
    {
        if ($this->isNew())
            throw new \Exception('Unable to delete object, record is new (and therefore doesn\'t exist in the database).');
            
        $conn = self::getConnection();
        $pk = self::getTablePk();
        $id = $this->id();
            
        // build sql statement (Use double quotes for Postgres identifiers)
        $sql = sprintf('DELETE FROM "%s" WHERE "%s" = ?', self::getTableName(), $pk);

        // prepare & execute
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]); // Pass array of values directly to execute
        } catch (\PDOException $e) {
             throw new \Exception($e->getMessage()."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }
    }

    /**
     * Fetch column names directly from the database (PostgreSQL style).
     * Using INFORMATION_SCHEMA is a more standard way for column metadata in Postgres/PDO.
     * * @access public
     * @return array
     */
    public function getColumnNames ()
    {
        $conn = self::getConnection();
        $tableName = self::getTableName();
        
        // Use standard SQL way to get columns in PDO/Postgres
        // NOTE: This assumes the table is in the public schema or a schema accessible to the connection.
        $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$tableName]);
            
            $ret = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($ret)) {
                 // Fallback or just throw if the table is truly not found
                 throw new \Exception(sprintf('Unable to fetch the column names for table "%s". Table might not exist.', $tableName));
            }
            return $ret;
        } catch (\PDOException $e) {
            throw new \Exception(sprintf('Unable to fetch the column names for table "%s". %s.', $tableName, $e->getMessage()), (int)$e->getCode(), $e);
        }
    }

    /**
     * Parse a value type. (Not strictly needed for PDO execute, 
     * but keeping the structure for consistency, although PDO handles binding better).
     * We can remove the implementation but keep the method for compatibility.
     * * @access private
     * @param mixed $value
     * @return string
     */
    private function parseValueType ($value)
    {
        // PDO's execute handles type conversion, so this is no longer critical
        // but for compatibility we return 's' (string) as a default if we kept mysqli_stmt::bind_param
        // However, since we are using PDO::execute, we can simplify/ignore this.
        return null;
    }

    /**
     * Get/set the parent object for this record.
     * Useful if you want to access the owning record without looking it up again.
     *
     * Use without parameters to return the parent object.
     * * @access public
     * @param object $obj
     * @return object
     */
    public function parent ($obj = false)
    {
        if ($obj && is_object($obj))
            $this->parentObject = $obj;

        return $this->parentObject;
    }

    /**
     * Revert the object by reloading our data.
     * * @access public
     * @param boolean $return If true the current object won't be reverted, it will return a new object via cloning.
     * @return void | clone
     */
    public function revert ($return = false)
    {
        if ($return)
        {
            $ret = clone $this;
            $ret->revert();

            return $ret;
        }

        $this->hydrateFromDatabase();
    }

    /**
     * Get a value for a particular field or all values.
     * * @access public
     * @param string $fieldName If false (default), the entire record will be returned as an array.
     * @return array | string
     */
    public function get ($fieldName = false)
    {
        // return all data
        if ($fieldName === false)
            return self::convertObjectToArray($this);

        return $this->{$fieldName};
    }
    
    /**
     * Convert an object to an array.
     *
     * @access public
     * @static
     * @param object $object
     * @return array
     */
    public static function convertObjectToArray ($object)
    { 
        if (!is_object($object))
            return $object;

        $array = array();
        $r = new ReflectionObject($object);

        // Filter public properties like the original code
        foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) AS $value)
        {
            $key = $value->getName();
            $value = $value->getValue($object);
        
            $array[$key] = is_object($value) ? self::convertObjectToArray($value) : $value;
        }

        return $array;
    }

    /**
     * Set a new value for a particular field.
     * * @access public
     * @param string $fieldName
     * @param mixed $newValue
     * @return $this
     */
    public function set ($fieldName, $newValue)
    {
        // if changed, mark object as modified
        // Note: Loose comparison '!=' is used here as in the original code.
        if (!isset($this->{$fieldName}) || $this->{$fieldName} != $newValue)
            $this->modifiedFields($fieldName, $newValue);

        $this->{$fieldName} = $newValue;
        
        return $this;
    }

    /**
     * Check if our record has been modified since boot up.
     * This is only available if you use set() to change the object.
     * * @access public
     * @return array | false
     */
    public function isModified ()
    {
        return (count($this->modifiedFields) > 0) ? $this->modifiedFields : false;
    }

    /**
     * Mark a field as modified & add the change to our history.
     * * @access private
     * @param string $fieldName
     * @param mixed $newValue
     * @return void
     */
    private function modifiedFields ($fieldName, $newValue)
    {
        // add modified field to a list
        if (!isset($this->modifiedFields[$fieldName]))
        {
            $this->modifiedFields[$fieldName] = $newValue;

            return;
        }

        // already modified, initiate a numerical array
        if (!is_array($this->modifiedFields[$fieldName]))
            $this->modifiedFields[$fieldName] = array($this->modifiedFields[$fieldName]);

        // add new change to array
        $this->modifiedFields[$fieldName][] = $newValue;
    }

    /**
     * Fetch & return one record only.
     */
    const FETCH_ONE = 1;

    /**
     * Fetch multiple records.
     */
    const FETCH_MANY = 2;
    
    /**
     * Don't fetch.
     */
    const FETCH_NONE = 3;

    /**
     * Execute an SQL statement & get all records as hydrated objects.
     * * @access public
     * @param string $sql
     * @param integer $return
     * @return mixed
     */
    public static function sql ($sql, $return = SimpleOrm::FETCH_MANY)
    {
        $conn = self::getConnection();
        $className = get_called_class();
        
        // shortcuts (using double quotes for Postgres identifiers)
        $sql = str_replace(
            array(':database', ':table', ':pk'), 
            array(self::getDatabaseName(), '"'.self::getTableName().'"', '"'.self::getTablePk().'"'), 
            $sql
        );
        
        // execute
        try {
            $result = $conn->query($sql);
        } catch (\PDOException $e) {
             throw new \Exception(sprintf('Unable to execute SQL statement. %s', $e->getMessage())."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }
        
        if ($return === SimpleOrm::FETCH_NONE)
            return;

        $ret = array();

        while ($row = $result->fetch(\PDO::FETCH_ASSOC))
            $ret[] = call_user_func_array(array($className, 'hydrate'), array($row));

        // return one if requested
        if ($return === SimpleOrm::FETCH_ONE)
            $ret = isset($ret[0]) ? $ret[0] : null;

        return $ret;
    }
    
    /**
     * Execute a Count SQL statement & return the number.
     * * @access public
     * @param string $sql
     * @return integer
     */
    public static function count ($sql)
    {
        $conn = self::getConnection();
        
        // shortcuts (using double quotes for Postgres identifiers)
        $sql = str_replace(
            array(':database', ':table', ':pk'), 
            array(self::getDatabaseName(), '"'.self::getTableName().'"', '"'.self::getTablePk().'"'), 
            $sql
        );
        
        // Execute and fetch the count (first column of the first row)
        try {
            $count = $conn->query($sql)->fetchColumn();
        } catch (\PDOException $e) {
             throw new \Exception(sprintf('Unable to execute SQL Count statement. %s', $e->getMessage())."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }
        
        return $count > 0 ? (int)$count : 0;
    }
    
    /**
     * Truncate the table.
     * All data will be removed permanently.
     * * @access public
     * @static
     * @return void
     */
    public static function truncate ()
    {
        // TRUNCATE TABLE :table (Note: :database is removed as it's not standard for TRUNCATE in Postgres/PDO)
        self::sql('TRUNCATE :table', SimpleOrm::FETCH_NONE);
    }

    /**
     * Get all records.
     * * @access public
     * @return array
     */
    public static function all ()
    {
        return self::sql("SELECT * FROM :table"); // :table already includes double quotes
    }

    /**
     * Retrieve a record by its primary key (PK).
     * * @access public
     * @param integer $pk
     * @return object
     */
    public static function retrieveByPK ($pk)
    {
        // Allow string PKs, but throw if empty
        if (empty($pk) && !is_numeric($pk))
            throw new \InvalidArgumentException('The PK must be a valid value.');

        $reflectionObj = new ReflectionClass(get_called_class());

        return $reflectionObj->newInstanceArgs(array($pk, SimpleOrm::LOAD_BY_PK));
    }

    /**
     * Load an ER object by array.
     * This skips reloading the data from the database.
     * * @access public
     * @param array $data
     * @return object
     */
    public static function hydrate ($data)
    {
        if (!is_array($data))
            throw new \InvalidArgumentException('The data given must be an array.');

        $reflectionObj = new ReflectionClass(get_called_class());

        return $reflectionObj->newInstanceArgs(array($data, SimpleOrm::LOAD_BY_ARRAY));
    }

    /**
     * Retrieve a record by a particular column name using the retrieveBy prefix.
     * * @access public
     * @static
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function __callStatic ($name, $args)
    {
        $class = get_called_class();

        if (substr($name, 0, 10) == 'retrieveBy')
        {
            // prepend field name to args
            $field = strtolower(preg_replace('/\B([A-Z])/', '_${1}', substr($name, 10)));
            array_unshift($args, $field);

            return call_user_func_array(array($class, 'retrieveByField'), $args);
        }

        throw new \Exception(sprintf('There is no static method named "%s" in the class "%s".', $name, $class));
    }

    /**
     * Retrieve a record by a particular column name.
     * * @access public
     * @static
     * @param string $field
     * @param mixed $value
     * @param integer $return
     * @return mixed
     */
    public static function retrieveByField ($field, $value, $return = SimpleOrm::FETCH_MANY)
    {
        if (!is_string($field))
            throw new \InvalidArgumentException('The field name must be a string.');
        
        $conn = self::getConnection();
        
        // Check for LIKE operator
        $operator = (strpos($value, '%') === false) ? '=' : 'ILIKE'; // ILIKE for case-insensitive LIKE in Postgres

        // build our query (using double quotes for Postgres identifier and prepared statement)
        $sql = sprintf('SELECT * FROM "%s" WHERE "%s" %s ?', self::getTableName(), $field, $operator);

        if ($return === SimpleOrm::FETCH_ONE)
            $sql .= ' LIMIT 1'; // LIMIT only, no offset 0 needed

        // execute
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$value]);
        } catch (\PDOException $e) {
             throw new \Exception(sprintf('Unable to execute SQL retrieveByField statement. %s', $e->getMessage())."\n\nSQL: ".$sql, (int)$e->getCode(), $e);
        }

        $ret = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            $ret[] = call_user_func_array(array(get_called_class(), 'hydrate'), array($row));

        // return one if requested
        if ($return === SimpleOrm::FETCH_ONE)
            return isset($ret[0]) ? $ret[0] : null;
            
        return $ret;
    }
    
    /**
     * Get array for select box.
     *
     * NOTE: Class must have __toString defined.
     * * @access public
     * @param string $where
     * @return array
     */
    public static function buildSelectBoxValues ($where = null)
    {
        $sql = 'SELECT * FROM :table'; // :table already includes double quotes
        
        // custom where?
        if (is_string($where))
            $sql .= sprintf(" WHERE %s", $where);
    
        $values = array();
        
        foreach (self::sql($sql) AS $object)
            $values[$object->id()] = (string) $object;
    
        return $values;
    }
}