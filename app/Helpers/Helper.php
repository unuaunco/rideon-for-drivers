<?php

function test($data)
{
    echo '<pre>';print_r($data);die;
}

function watch($data)
{
    Log::info($data);
}

function errorLog($e)
{
    Log::info($e->getMessage() . ', ' . basename($e->getFile()) . '_line: ' . $e->getLine());
    return;
}
