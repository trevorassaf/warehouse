CREATE DATABASE test_db;

CREATE TABLE test_db.foo(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	name VARCHAR(20) NOT NULL,
	UNIQUE KEY(name));

CREATE TABLE test_db.bar(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);

CREATE TABLE test_db.baz(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	value VARCHAR(1) NOT NULL);

INSERT INTO test_db.baz (value) VALUES (a);
INSERT INTO test_db.baz (value) VALUES (ab);
INSERT INTO test_db.baz (value) VALUES (abc);


CREATE TABLE test_db.bar_foo_join_table(
        foo_id BIGINT UNSIGNED NOT NULL,
        bar_id BIGINT UNSIGNED NOT NULL,
        FOREIGN KEY(foo_id) REFERENCES test_db.foo(id),
        FOREIGN KEY(bar_id) REFERENCES test_db.bar(id));

