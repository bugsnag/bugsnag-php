<?php

class Abc
{
    /**
     * Class constants called 'class' are not allowed by PHP because it would break
     * the class name lookup feature; e.g. Abc::class should always equal "Abc"
     */
    const class = ':-)';
}
