<?php

if (!function_exists('str_split_to_options')) {

    /**
     * str to configure options
     * @param string $str
     * @param string $separator
     * @param string $group
     * @return array
     */
    function str_split_to_options(string $str, string $separator = ',', string $group = ':'): array
    {
        $options = [];
        $v = explode($separator, $str);
        if ($v) {
            foreach ($v as $groups) {
                $value = explode($group, trim($groups));
                if (count($value) === 2) {
                    $tmp = $value[1];
                    if (is_numeric($value[1])) {
                        $tmp = (int) $value[1];
                        if (strpos($value[1], '.') !== false) {
                            $tmp = (float) $value[1];
                        }
                    }
                    $options[$value[0]] = $tmp;
                }
            }
        }
        return $options;
    }
}
