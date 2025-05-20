<?php

namespace Azuracom\SpreadsheetToObjectBundle\Helper;

class DataMatcher
{
    protected $datas = array();
    protected $matches = array();
    protected $keyFormatterCallback;
    protected $currentUniqueId = null;
    protected $currentType = null;
    protected $adder = array();

    public function reset($types = null)
    {
        if ($types === null) {
            $this->datas = array();
            $this->matches = array();
            return;
        }

        foreach ($types as $type) {
            unset($this->datas[$type]);
            unset($this->matches[$type]);
        }
    }

    public function addData(string $type, $data, $uniqueId = null)
    {
        $this->currentUniqueId = $uniqueId ? $uniqueId : $this->guessUniqueKey($type, $data);
        $this->currentType = $type;
        $this->datas[$type][$this->currentUniqueId] = $data;

        return $this;
    }

    public function addMatch($match, $matchKey = 0, string $type = null, $uniqueId = null)
    {
        $type = $type ? $type : $this->currentType;
        $uniqueId = $uniqueId ? $uniqueId : $this->currentUniqueId;
        $match = $this->formatKey($match, $this->currentType, $matchKey);
        $this->matches[$this->currentType][$matchKey][$match] = $this->currentUniqueId;

        return $this;
    }

    public function guessUniqueKey(string $type, $data)
    {
        if (is_array($data) && isset($data['id']) && $data['id']) {
            return $data['id'];
        }

        if (is_object($data) && method_exists($data, 'getId') && $data->getId()) {
            return $data->getId();
        }

        return uniqid($type . "_");
    }

    public function setMatchesForData(string $type, $uniqueId, array $matches)
    {
        if (!isset($this->matches[$type])) {
            $this->matches[$type] = [];
        }

        $this->convertMatches($type, $matches);

        foreach ($matches as $matchKey => $match) {
            $this->matches[$type][$matchKey][$this->formatKey($match, $type, $matchKey)] = $uniqueId;
        }

        return $this;
    }

    public function findData(string $type, $matches)
    {
        $key = $this->getDataKey($type, $matches);
        if ($key !== null) {
            return $this->getDataAtKey($type, $key);
        }

        return null;
    }

    private function convertMatches(string $type, array &$matches)
    {
        //check if data has only one matches
        if (!is_array($matches)) {
            if (count($this->matches[$type]) > 1) {
                throw new \Exception("If data has many matches, argument matches must be an array");
            }

            $key = array_key_first($this->matches[$type]) ?? 0;

            $matches = [
                $key => $matches
            ];
        }

        return $this;
    }

    public function getDataKey(string $type, $matches)
    {
        $matches = is_array($matches) ? $matches : [$matches];

        if (!isset($this->matches[$type])) {
            return null;
        }

        $this->convertMatches($type, $matches);

        foreach ($matches as $matchKey => $match) {
            if ($match === null) {
                continue;
            }

            $match = $this->formatKey($match, $type, $matchKey);

            if (isset($this->matches[$type][$matchKey]) && isset($this->matches[$type][$matchKey][$match])) {
                return $this->matches[$type][$matchKey][$match];
            }
        }

        return null;
    }

    public function getDataAtKey(string $type, $key)
    {
        return isset($this->datas[$type][$key]) ? $this->datas[$type][$key] : null;
    }

    public function getDatas()
    {
        return $this->datas;
    }

    public function getMatches()
    {
        return $this->matches;
    }

    public function formatKey($value, string $type = null, $matchKey = null)
    {
        if (!is_array($value)) {
            $value = array($value);
        }

        $key = "";

        foreach ($value as $tmp) {
            $key .= $this->applyFormat($tmp, $type, $matchKey) . "|";
        }
        $key = substr($key, 0, -1);
        return $key;
    }

    private function applyFormat($value, string $type = null, $matchKey = null)
    {
        if (!$callback = $this->keyFormatterCallback) {
            return trim(strtolower($value));
        }

        return $callback($value, $type, $matchKey);
    }

    /**
     * Get the value of keyFormatterCallback
     */
    public function getKeyFormatterCallback()
    {
        return $this->keyFormatterCallback;
    }

    /**
     * Set the value of keyFormatterCallback
     *
     * @return  self
     */
    public function setKeyFormatterCallback(callable $keyFormatterCallback = null)
    {
        $this->keyFormatterCallback = $keyFormatterCallback;

        return $this;
    }

    /**
     * Get the value of currentUniqueId
     */
    public function getCurrentUniqueId()
    {
        return $this->currentUniqueId;
    }

    public function createAdder(string $type, callable $callable)
    {
        $this->adder[$type] = $callable;
    }

    public function getAdder($type)
    {
        return isset($this->adder[$type]) ? $this->adder[$type] : null;
    }
}
