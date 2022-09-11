<?php

/**
 * Simple class to output CSV data
 * Uses CollectionCore
 *
 * @since 1.9.1.0
 */
class CSVCore
{
    // @codingStandardsIgnoreStart
    public $filename;
    public $collection;
    public $delimiter;
    // @codingStandardsIgnoreEnd

    /**
    * Loads objects, filename and optionnaly a delimiter.
    * @param array|Iterator $collection Collection of objects / arrays (of non-objects)
    * @param string $filename : used later to save the file
    * @param string $delimiter Optional : delimiter used
    */
    public function __construct($collection, $filename, $delimiter = ';')
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
        $this->collection = $collection;
    }

    /**
     * Main function
     * Adds headers
     * Outputs
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function export()
    {
        $this->headers();

        $headerLine = false;

        foreach ($this->collection as $object) {
            $vars = get_object_vars($object);
            if (!$headerLine) {
                $this->output(array_keys($vars));
                $headerLine = true;
            }

            // outputs values
            $this->output($vars);
            unset($vars);
        }
    }

    /**
     * Wraps data and echoes
     * Uses defined delimiter
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function output($data)
    {
        $wrappedData = array_map(['CSVCore', 'wrap'], $data);
        echo sprintf("%s\n", implode($this->delimiter, $wrappedData));
    }

    /**
     * Escapes data
     * @param string $data
     * @return string $data
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function wrap($data)
    {
        $data = str_replace(['"', ';'], '', $data);

        return sprintf('"%s"', $data);
    }

    /**
    * Adds headers
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
    */
    public function headers()
    {
        header('Content-type: text/csv');
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-disposition: attachment; filename="'.$this->filename.'.csv"');
    }
}
