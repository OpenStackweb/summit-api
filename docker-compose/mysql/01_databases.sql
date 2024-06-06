-- create databases
CREATE DATABASE IF NOT EXISTS `api_test`;
CREATE DATABASE IF NOT EXISTS `model_test`;

-- create root user and grant rights
CREATE USER 'root'@'localhost' IDENTIFIED BY '1qaz2wsx!';
GRANT ALL ON *.* TO 'root'@'localhost';
FLUSH PRIVILEGES;