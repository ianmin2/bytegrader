<?php
include __DIR__."/workers/grading/index.php";

echo new GradingWorker( null, [1], [1], true);