CREATE DATABASE test_db;

CREATE TABLE test_db.foo(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	name VARCHAR(10) NOT NULL,
	age INT UNSIGNED) NOT NULL,
	barId BIGINT UNSIGNED NOT NULL,
	UNIQUE KEY(name),
	UNIQUE KEY(name, age));

INSERT INTO test_db.foo (name, age) VALUES (trevor, 21);


CREATE TABLE test_db.bar(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);



ALTER TABLE test_db.foo
	ADD FOREIGN KEY(barId) REFERENCES test_db.foo(id);




