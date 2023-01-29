<?php

namespace App\Application\Library;

/**
 * From:
 * https://github.com/laynefyc/php-monitor/blob/master/src/common/Profile.php
 */
class ProfileDataParser
{
    const NO_PARENT = '__top__';

    protected array $_keys = ['ct', 'wt', 'cpu', 'mu', 'pmu'];
    protected array $_exclusiveKeys = ['ewt', 'ecpu', 'emu', 'epmu'];

    protected array $_collapsed = [];
    protected array $_indexed = [];
    protected array $_visited = [];
    protected array $_nodes = [];

    public function __construct($profile)
    {
        $result = [];
        foreach ($profile as $name => $values) {
            [$parent, $func] = $this->splitName($name);
            if (isset($result[$func])) {
                $result[$func] = $this->sumKeys($result[$func], $values);
                $result[$func]['p'][] = $parent;
            } else {
                foreach ($this->_keys as $v) {
                    $result[$func][$v] = $values[$v];
                    $result[$func]['e' . $v] = $values[$v];
                }
                $result[$func]['p'] = [$parent];
            }
            // Build the indexed data.
            if ($parent === null) {
                $parent = self::NO_PARENT;
            }
            if (!isset($this->_indexed[$parent])) {
                $this->_indexed[$parent] = [];
            }
            $this->_indexed[$parent][$func] = $values;
        }
        $this->_collapsed = $result;
    }

    protected function splitName($name): array
    {
        $a = explode("==>", $name);
        return isset($a[1]) ? $a : [null, $a[0]];
    }

    protected function sumKeys(array $a, array $b): array
    {
        foreach ($this->_keys as $key) {
            if (!isset($a[$key])) {
                $a[$key] = 0;
            }
            $a[$key] += $b[$key] ?? 0;
        }
        return $a;
    }

    protected function getChildren($symbol, $metric = null, $threshold = 0): array
    {
        $children = [];
        if (!isset($this->_indexed[$symbol])) {
            return $children;
        }

        $total = 0;
        if (isset($metric)) {
            $top = $this->_indexed[self::NO_PARENT];
            // Not always 'main()'
            $mainFunc = current($top);
            $total = $mainFunc[$metric];
        }

        foreach ($this->_indexed[$symbol] as $name => $data) {
            if (
                $metric && $total > 0 && $threshold > 0 &&
                ($this->_collapsed[$name][$metric] / $total) < $threshold
            ) {
                continue;
            }
            $children[] = $data + ['function' => $name];
        }
        return $children;
    }

    /**
     * Generate the approximate exclusive values for each metric.
     *
     * We get a==>b as the name, we need a key for a and b in the array
     * to get exclusive values for A we need to subtract the values of B (and any other children);
     * call passing in the entire profile only, should return an array of
     * functions with their regular timing, and exclusive numbers inside ['exclusive']
     *
     * Consider:
     *              /---c---d---e
     *          a -/----b---d---e
     *
     * We have c==>d and b==>d, and in both instances d invokes e, yet we will
     * have but a single d==>e result. This is a known and documented limitation of XHProf
     *
     * We have one d==>e entry, with some values, including ct=2
     * We also have c==>d and b==>d
     *
     * We should determine how many ==>d options there are, and equally
     * split the cost of d==>e across them since d==>e represents the sum total of all calls.
     *
     * Notes:
     *  Function names are not unique, but we're merging them
     *
     * @return ProfileDataParser A new instance with exclusive data set.
     */
    protected function calculateSelf(): ProfileDataParser
    {
        // Init exclusive values
        foreach ($this->_collapsed as &$data) {
            $data['ewt'] = $data['wt'];
            $data['emu'] = $data['mu'];
            $data['ecpu'] = $data['cpu'];
            $data['ect'] = $data['ct'];
            $data['epmu'] = $data['pmu'];
        }
        unset($data);

        // Go over each method and remove each childs metrics
        // from the parent.
        foreach ($this->_collapsed as $name => $data) {
            $children = $this->getChildren($name);
            foreach ($children as $child) {
                $this->_collapsed[$name]['ewt'] -= $child['wt'];
                $this->_collapsed[$name]['emu'] -= $child['mu'];
                $this->_collapsed[$name]['ecpu'] -= $child['cpu'];
                $this->_collapsed[$name]['ect'] -= $child['ct'];
                $this->_collapsed[$name]['epmu'] -= $child['pmu'];
            }
        }
        return $this;
    }

    /**
     * @param $metric
     * @return int
     */
    protected function maxValue($metric): int
    {
        return array_reduce(
            $this->_collapsed,
            function ($result, $item) use ($metric) {
                if ($item[$metric] > $result) {
                    return $item[$metric];
                }
                return $result;
            },
            0
        );
    }

    /**
     * Return a structured array suitable for generating flamegraph visualizations.
     *
     * Functions whose inclusive time is less than 1% of the total time will
     * be excluded from the callgraph data.
     *
     * @return array
     */
    public function getFlamegraph($metric = 'wt', $threshold = 0.01): array
    {
        $valid = array_merge($this->_keys, $this->_exclusiveKeys);
        if (!in_array($metric, $valid)) {
            throw new \InvalidArgumentException("Unknown metric '$metric'. Cannot generate flamegraph.");
        }
        $this->calculateSelf();

        // Non exclusive metrics are always main() because it is the root call scope.
        if (in_array($metric, $this->_exclusiveKeys)) {
            $main = $this->maxValue($metric);
        } else {
            $main = $this->_collapsed['main()'][$metric];
        }

        $this->_visited = $this->_nodes = [];
        $flamegraph = $this->flamegraphData(self::NO_PARENT, $main, $metric, $threshold);

        return [
            'data' => array_shift($flamegraph),
            'sort' => $this->_visited,
        ];
    }

    protected function flamegraphData($parentName, $main, $metric, $threshold): array
    {
        $result = [];
        // Leaves don't have children, and don't have links/nodes to add.
        if (!isset($this->_indexed[$parentName])) {
            return $result;
        }

        $children = $this->_indexed[$parentName];
        foreach ($children as $childName => $metrics) {
            $metrics = $this->_collapsed[$childName];
            if ($metrics[$metric] / $main <= $threshold) {
                continue;
            }
            $current = [
                'name' => $childName,
                'value' => $metrics[$metric],
            ];
            $revisit = false;

            // Keep track of which nodes we've visited and their position
            // in the node list.
            if (!isset($this->_visited[$childName])) {
                $index = count($this->_nodes);
                $this->_visited[$childName] = $index;
                $this->_nodes[] = $current;
            } else {
                $revisit = true;
                $index = $this->_visited[$childName];
            }

            // If the current function has more children,
            // walk that call subgraph.
            if (isset($this->_indexed[$childName]) && !$revisit) {
                $grandChildren = $this->flamegraphData($childName, $main, $metric, $threshold);
                if (!empty($grandChildren)) {
                    $current['children'] = $grandChildren;
                }
            }

            $result[] = $current;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getProfileBySort(): array
    {
        $arr = [];
        foreach ($this->_collapsed as $k => $val) {
            $arr[] = [
                'id' => $k,
                'ct' => $val['ct'],
                'ecpu' => $val['ecpu'],
                'ewt' => $val['wt'] - $val['ewt'] ,
                'emu' => $val['mu'] - $val['emu'] ,
                'epmu' => $val['pmu'] - $val['epmu']
            ];
        }
        usort($arr, function ($a, $b) {
            return $a['ewt'] > $b['ewt'] ? -1 : 1;
        });
        return $arr;
    }
}
