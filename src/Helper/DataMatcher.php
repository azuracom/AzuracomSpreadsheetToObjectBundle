<?php

namespace Azuracom\SpreadsheetToObjectBundle\Helper;

class DataMatcher
{
    protected array $datas = [];
    protected array $matches = [];
    protected ?\Closure $keyFormatterCallback = null;
    protected mixed $currentUniqueId = null;
    protected ?string $currentType = null;
    protected array $adder = [];

    public function reset(?array $types = null)
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

    public function addData(string $type, mixed $data, string|int|null $uniqueId = null): static
    {
        $this->currentUniqueId = $uniqueId ? $uniqueId : $this->guessUniqueKey($type, $data);
        $this->currentType = $type;
        $this->datas[$type][$this->currentUniqueId] = $data;

        return $this;
    }

    public function addMatch(
        string|array $match,
        string|int $matchKey = 0,
        ?string $type = null,
        string|int|null $uniqueId = null
    ): static {
        $type = $type ? $type : $this->currentType;
        $uniqueId = $uniqueId ? $uniqueId : $this->currentUniqueId;
        $match = $this->formatKey($match, $this->currentType, $matchKey);
        $this->matches[$this->currentType][$matchKey][$match] = $this->currentUniqueId;

        return $this;
    }

    public function guessUniqueKey(string $type, mixed $data): string
    {
        if (is_array($data) && isset($data['id']) && $data['id']) {
            return $data['id'];
        }

        if (is_object($data) && method_exists($data, 'getId') && $data->getId()) {
            return $data->getId();
        }

        return uniqid($type . "_");
    }

    public function setMatchesForData(string $type, string|int $uniqueId, array $matches): static
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

    public function findData(string $type, $matches): mixed
    {
        $key = $this->getDataKey($type, $matches);
        if ($key !== null) {
            return $this->getDataAtKey($type, $key);
        }

        return null;
    }

    private function convertMatches(string $type, array &$matches): static
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

    public function getDataKey(string $type, mixed $matches): mixed
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

    public function getDataAtKey(string $type, string|int $key): mixed
    {
        return isset($this->datas[$type][$key]) ? $this->datas[$type][$key] : null;
    }

    public function getDatas(): array
    {
        return $this->datas;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function formatKey(mixed $value, ?string $type = null, string|int|null $matchKey = null): string
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

    private function applyFormat(mixed $value, ?string $type = null,  string|int|null $matchKey = null): mixed
    {
        if (!$callback = $this->keyFormatterCallback) {
            return trim(strtolower($value));
        }

        return $callback($value, $type, $matchKey);
    }

    /**
     * Get the value of keyFormatterCallback
     */
    public function getKeyFormatterCallback(): ?callable
    {
        return $this->keyFormatterCallback;
    }

    /**
     * Set the value of keyFormatterCallback
     *
     * @return  self
     */
    public function setKeyFormatterCallback(?callable $keyFormatterCallback = null)
    {
        $this->keyFormatterCallback = \Closure::fromCallable($keyFormatterCallback);

        return $this;
    }

    /**
     * Get the value of currentUniqueId
     */
    public function getCurrentUniqueId(): mixed
    {
        return $this->currentUniqueId;
    }

    public function createAdder(string $type, callable $callable): void
    {
        $this->adder[$type] = $callable;
    }

    public function getAdder($type): ?callable
    {
        return isset($this->adder[$type]) ? $this->adder[$type] : null;
    }
}
