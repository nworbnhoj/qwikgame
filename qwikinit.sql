
CREATE USER alpha WITH PASSWORD 'password';
ALTER ROLE alpha SET client_encoding TO 'utf8';
ALTER ROLE alpha SET default_transaction_isolation TO 'read committed';
ALTER ROLE alpha SET timezone TO 'UTC';
CREATE DATABASE alpha with OWNER = alpha;


CREATE USER beta WITH PASSWORD 'password';
ALTER ROLE beta SET client_encoding TO 'utf8';
ALTER ROLE beta SET default_transaction_isolation TO 'read committed';
ALTER ROLE beta SET timezone TO 'UTC';
CREATE DATABASE beta with OWNER = beta;


CREATE USER qwikgame WITH PASSWORD 'password';
ALTER ROLE qwikgame SET client_encoding TO 'utf8';
ALTER ROLE qwikgame SET default_transaction_isolation TO 'read committed';
ALTER ROLE qwikgame SET timezone TO 'UTC';
CREATE DATABASE qwikgame with OWNER = qwikgame;