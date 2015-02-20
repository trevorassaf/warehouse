CREATE DATABASE test_db;

CREATE TABLE test_db.foo(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	name VARCHAR(10) NOT NULL,
	age INT UNSIGNED NOT NULL,
	UNIQUE KEY(name),
	UNIQUE KEY(name, age));

INSERT INTO test_db.foo (name, age) VALUES ("trevor", 21);
INSERT INTO test_db.foo (name, age) VALUES ("kalyna", 22);


CREATE TABLE test_db.bar(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	name VARCHAR(10) NOT NULL);

INSERT INTO test_db.bar (name) VALUES ("bar-col");


CREATE TABLE test_db.foo_bar_join_table(
	id SERIAL,
	PRIMARY KEY(id),
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	lastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	barId BIGINT UNSIGNED NOT NULL,
	fooId BIGINT UNSIGNED NOT NULL);

INSERT INTO test_db.foo_bar_join_table (barId, fooId) VALUES (1, 1);
INSERT INTO test_db.foo_bar_join_table (barId, fooId) VALUES (1, 2);






ALTER TABLE test_db.foo_bar_join_table
	ADD FOREIGN KEY(barId) REFERENCES test_db.bar(id);
ALTER TABLE test_db.foo_bar_join_table
	ADD FOREIGN KEY(fooId) REFERENCES test_db.foo(id);


