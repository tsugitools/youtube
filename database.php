<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
  "drop table if exists {$CFG->dbprefix}youtube_views;",
  "drop table if exists {$CFG->dbprefix}youtube_views_by_student;"
);

// The SQL to create the necessary tables if they don't exist

$buckets = "";
for($i=0;$i<120;$i++) {
    if ( strlen($buckets) > 0 ) $buckets .= ",\n";
    $j = "b".str_pad($i."", 3, "0", STR_PAD_LEFT);
    $buckets .= '    ' . $j . ' INT NOT NULL DEFAULT 0';
}
$buckets .= ",\n";

$DATABASE_INSTALL = array(

  array( "{$CFG->dbprefix}youtube_views",
  "CREATE TABLE {$CFG->dbprefix}youtube_views (
    link_id             INTEGER NOT NULL,
    seconds             INTEGER NOT NULL,
    width               INTEGER NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

".$buckets."

    CONSTRAINT {$CFG->dbprefix}youtube_views_ibfk_1
        FOREIGN KEY (link_id)
        REFERENCES {$CFG->dbprefix}lti_link (link_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)

  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  "),

  array( "{$CFG->dbprefix}youtube_views_user",
  "CREATE TABLE {$CFG->dbprefix}youtube_views_user (
    link_id             INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    seconds             INTEGER NOT NULL,
    width               INTEGER NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

".$buckets."

    CONSTRAINT {$CFG->dbprefix}youtube_views_user_ibfk_1
        FOREIGN KEY (link_id)
        REFERENCES {$CFG->dbprefix}lti_link (link_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT {$CFG->dbprefix}youtube_views_user_ibfk_2
        FOREIGN KEY (user_id)
        REFERENCES {$CFG->dbprefix}lti_user (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id)

  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  "),


);

