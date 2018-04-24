<?php
/**
 * @copyright 2012-2018 Hostnet B.V.
 */
declare(strict_types=1);

class PrimeVisitor extends AbstractNodeElementVisitorInterface
{

    /**
     *
     * @var FileChange
     */
    private $file_change = null;

    /**
     *
     * @var Versioning
     */
    private $versioning = null;

    /**
     * @var FileFunction[]
     */
    private $functions = [];

    /**
     *
     * @var array[string]PrimeData
     */
    private $data = array();

    /**
     *
     * @var string
     */
    private $prefix = null;

    public function visitVersioning(Versioning &$versioning)
    {
        $this->versioning = $versioning;
    }

    public function visitFileChange(FileChange &$file_change)
    {
        $this->file_change = $file_change;
    }

    public function visitFunctionName(FileFunction $file_function)
    {
        $this->functions[] = $file_function->getFunction();
    }

    public function visitNode(Node &$node)
    {
        $changed_at     = "";
        $dead           = false;
        $file_functions = [];

        if ($this->versioning !== null) {
            $last_change = $this->versioning->getLastChange();
            if ($last_change !== null) {
                $timezone = new DateTimeZone(date_default_timezone_get());

                $last_change->setTimezone($timezone);
                $changed_at = $last_change->format("Y-m-d H:i:s");
            }
            $this->versioning = null;
        }

        if ($this->file_change !== null) {
            $dead = is_null($this->file_change->getDeletedAt());
        }

        foreach ($this->functions as $function) {
            $file_functions[] = $function;
        }

        $prime_data = new PrimeData($changed_at, $dead, $file_functions);

        if ($this->prefix) {
            $this->data [$this->prefix.$node->getPath()] = $prime_data;
        } else {
            $this->data [$node->getFullPath()] = $prime_data;
        }
    }

    /**
     *
     * @return array[int]PrimeData
     */
    public function getPrimeData()
    {
        return $this->data;
    }

    public function reset()
    {
        $this->data = array();
    }

    public function resetFunctions()
    {
        $this->functions = [];
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
