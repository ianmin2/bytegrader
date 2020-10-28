<?php

function createGreeter($who, $dot=".") {
    return function() use ($who, $dot) {
        echo "Hello {$who}{$dot}";
    };
}

$greeter = createGreeter("World");
$greeter(); // Hello World


?>