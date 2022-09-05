<?php

$REGISTER_LTI2 = array(
"name" => "YouTube",
"FontAwesome" => "fa-youtube",
"short_name" => "YouTube",
"description" => "This tool allows you to track as students access and watch a YouTube video.
You can track both student launches and viewing behavior within the video.
You can assign grades to students for watching the video or based on how much of the video 
they have watched.
",
    "messages" => array("launch", "launch_grade"),
    "privacy_level" => "anonymous",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "analytics" => array(
        "internal"
    ),
    "source_url" => "https://github.com/tsugitools/youtube",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
        "store/screen-01.png",
        "store/screen-02.png",
        "store/screen-03.png",
        "store/screen-views.png",
        "store/screen-analytics.png"
    )
);
