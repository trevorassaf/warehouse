-- Create SQL Tables for Model --
/**
 * SupportedDbs
 *  - stores the databases that are supported by the warehouse.
 *  - columns:
 *    - name: the name of the database used in a particular warehouse app.
 */
CREATE TABLE SupportedDbs(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  PRIMARY KEY(id)
);

/**
 *  DtCategories
 *  - represents the general categories that users will use to identify datatypes.
 *  - columns:
 *    - name: the name of the category.
 */
CREATE TABLE DtCategories(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  PRIMARY KEY(id)
);

/**
 * DbDataType
 *  - represents the constrains for a datatype in a db
 *  - columns:
 *    - name: string identifier for the field, ex. "INT"
 *    - accepts_length: boolean value that is true
 *        iff the length of the datatype is variable.
 *    - requires_length: boolean value that is true
 *        iff the length of the field must be specified
 *        by the user. That is, it has no default.
 *        Note: this value is meaningless if 'accepts_length'
 *        is false.
 *    - default_size: the default size of the datatype.
 *        Note: this value is meaningless if 'requires_length'
 *        is true.
 *    - maximum_length: the maximum length of this field.
 *        Note: this value is meaningless if 'accepts_length'
 *        is false.
 *    - category_id: foreign key to the string identifier for the general category of 
 *        data that the particular data-type falls into. Users
 *        rarely care to distinguish between an INT and a TINYINT,
 *        they're more concerned with distinctions such as "Number"
 *        vs "Word."
 *    - db_id: foreign-key to the database that this data-type 
 *        belongs to.
 */
CREATE TABLE DbDataTypes(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  accepts_length TINYINT NOT NULL,
  requires_length TINYINT NOT NULL,
  default_length INT, -- set only if accepts_length is false. size is in bytes
  maximum_length INT,   
  category_id INT NOT NULL,
  db_id INT NOT NULL,
  PRIMARY KEY(id),
  FOREIGN KEY(category_id) REFERENCES DtCategories(id),
  FOREIGN KEY(db_id) REFERENCES SupportedDbs(id)
);

/**
 * WhUser
 * - represents a user in the warehouse 
 * - columns:
 *  - first_name: user's first name
 *  - last_name: user's last name
 *  - password: user's password
 *  - username: user's username
 *  - email: user's email
 */
CREATE TABLE WhUsers(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  first_name VARCHAR(20) NOT NULL,
  last_name VARCHAR(20) NOT NULL,
  password VARCHAR(40) NOT NULL UNIQUE,
  username VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(40) NOT NULL UNIQUE
);  

/**
 * WhApplication
 * - represents an application in the warehouse
 * - columns:
 *  - name: table name
 *  - owner_id: foreign key to user that currently owns the app
 *  - creator_id: foreign key to user that created the app
 */
CREATE TABLE WhApplications(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  owner_id INT NOT NULL,
  creator_id INT NOT NULL,
  FOREIGN KEY(owner_id) REFERENCES WhUsers(id),
  FOREIGN KEY(creator_id) REFERENCES WhUsers(id)
);  

/**
 * WhDatabase
 * - represents a db in a warehouse app.
 * - columns:
 *  - name: table name
 *  - app_id: foreign key to parent warehouse app 
 */
CREATE TABLE WhDatabases(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  app_id INT NOT NULL,
  FOREIGN KEY(app_id) REFERENCES WhApplications(id)
);  

/**
 * WhTables
 * - represents a table in a warehouse app.
 * - columns:
 *  - name: table name
 *  - db_id: foreign key to parent warehouse db
 */
CREATE TABLE WhTables(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  db_id INT NOT NULL,
  FOREIGN KEY(db_id) REFERENCES WhDatabases(id)
);  

/**
 * WhColumns
 *  - represents a column in a warehouse app
 *  - columns:
 *    - name: field name
 *    - length: length of this field.
 *    - is_unique: specifies that the field is unique
 *        Note: this will never be the primary key because
 *        the warehouse automatically gives each table an auto-inc id.
 *    - is_foreign_key: specifies that the field is a foreign key 
 *        to another table in the same warhouse app. 
 *        Note: 'is_unique' and 'is_foreign_key' can't be true at the same time
 *    - foreign_table_id: specifies id of table that the foreign key references.
 *    - allows_null: specifies that the field is not null. Note: this can't
 *        be true if 'is_unique' is true.
 *    - table_id: foreign key to the warehouse table that this column is 
 *        part of.
 */
CREATE TABLE WhColumns(
  id INT NOT NULL UNIQUE AUTO_INCREMENT,
  created DATETIME NOT NULL,
  last_updated DATETIME NOT NULL,
  name VARCHAR(20) NOT NULL,
  length INT,
  is_unique TINYINT NOT NULL,
  is_foreign_key TINYINT NOT NULL,
  foreign_table_id INT NOT NULL,
  allows_null TINYINT NOT NULL,
  table_id INT NOT NULL,
  dt_id INT NOT NULL,
  FOREIGN KEY(table_id) REFERENCES WhTables(id),
  FOREIGN KEY(dt_id) REFERENCES DbDataTypes(id)
);  
