<?php
function sleepMilliseconds($milliseconds) {
    usleep($milliseconds * 1000);
}