DROP TABLE IF EXISTS `system__session`;
CREATE TABLE `system__session`
(
    `session_id` VARBINARY(128)   NOT NULL PRIMARY KEY,
    `modified`   INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `data`       BLOB             NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_bin;
