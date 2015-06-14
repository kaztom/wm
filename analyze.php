<?php

class Analize
{
    public $config = null;

    function main ()
    {
        $config = parse_ini_file('./config.ini');
        $this->config = $config;

        // ファイルの読み込み
        $html_source = file_get_contents('./output/output.html');

        // データを行ごとに配列変換
        $line_list = explode("\n", $html_source);

        // データ抽出
        $data_list = [];
        foreach ($line_list as $line) {
            if (strpos($line, '<td class="day">')) {
                // 同日のデータが復数ある場合はdayが空配列になるため前日のデータを一時的に保持
                if (!empty($day[1])) {
                    $tmp_day = $day[1];
                }
                if (preg_match('/<td class="day">([0-9]*)/', $line, $day)) {
                    // if this day is empty, use tmp day
                    if (empty($day[1])) {
                        $day[1] = $tmp_day;
                    } else {
                        $data_list[$day[1]] = [];
                    }
                }
            }
            else if (preg_match('/<td class="weight"><span>([0-9]*)\.([0-9]*)/', $line, $weight)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $weight[1] . "." . $weight[2],
                    $day[1],
                    'weight'
                );
            }
            else if (preg_match('/<td class="bmi"><span>([0-9]*)\.([0-9]*)/', $line, $bmi)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $bmi[1] . "." . $bmi[2],
                    $day[1],
                    'bmi'
                );
            }
            else if (preg_match('/<td class="bodyFat"><span>([0-9]*)\.([0-9]*)/', $line, $bf)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $bf[1] . "." . $bf[2],
                    $day[1],
                    'bf'
                );
            }
            else if (preg_match('/<td class="skeletalMuscles"><span>([0-9]*)\.([0-9]*)/', $line, $sm)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $sm[1] . "." . $sm[2],
                    $day[1],
                    'sm'
                );
            }
            else if (preg_match('/<td class="metabolism"><span>([0-9]*)\,([0-9]*)/', $line, $mtb)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $mtb[1] . $mtb[2],
                    $day[1],
                    'mtb'
                );
            }
            else if (preg_match('/<td class="visceralFat"><span>([0-9]*)\.([0-9]*)/', $line, $vf)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $vf[1] . "." . $vf[2],
                    $day[1],
                    'vf'
                );
            }
            else if (preg_match('/<td class="age"><span>([0-9]*)/', $line, $age)) {
                $data_list = $this->pushDataOnDuplicate(
                    $data_list,
                    $age[1],
                    $day[1],
                    'age'
                );
            }
        }
        $data_list = $this->clearEmptyData($data_list);
        
        file_put_contents($this->config['json_path'], $this->formatForZingChart($data_list));
    }

    public function pushDataOnDuplicate ($data_list, $real_data, $day, $type)
    {
        if (!empty($data_list[$day][$type])) {
            if ($this->config['duplicate_format']) {
                $count = floor(count($data_list[$day]) / 7) + 1;
                $data_list[$day][$type . $count] = $real_data;
            }
        } else {
            $data_list[$day][$type] = $real_data;
        }

        return $data_list;
    }

    public function clearEmptyData ($data_list)
    {
        if ($this->config['clear_empty_data']) {
            foreach ($data_list as $day => $data) {
                if (count($data) < 2) {
                    unset($data_list[$day]);
                }
            }
        }

        return $data_list;
    }

    public function formatForZingChart ($data_list)
    {
        $day_list    = '';
        $weight_list = '';
        $bmi_list    = '';
        $bf_list     = '';
        $sm_list     = '';
        $mtb_list    = '';
        $vf_list     = '';
        $age_list    = '';

        foreach ($data_list as $day => $data) {
            $day_list    .= $day . ',';
            $weight_list .= $data['weight'] . ',';
            $bmi_list    .= $data['bmi'] . ',';
            $bf_list     .= $data['bf'] . ',';
            $sm_list     .= $data['sm'] . ',';
            $mtb_list    .= $data['mtb'] . ',';
            $vf_list     .= $data['vf'] . ',';
            $age_list    .= $data['age'] . ',';
        }
        // いらないカンマ削る
        $day_list    = substr($day_list   , 0, strlen($day_list) - 1);
        $weight_list = substr($weight_list, 0, strlen($weight_list) - 1);
        $bmi_list    = substr($bmi_list   , 0, strlen($bmi_list) - 1);
        $bf_list     = substr($bf_list    , 0, strlen($bf_list) - 1);
        $sm_list     = substr($sm_list    , 0, strlen($sm_list) - 1);
        $mtb_list    = substr($mtb_list   , 0, strlen($mtb_list) - 1);
        $vf_list     = substr($vf_list    , 0, strlen($vf_list) - 1);
        $age_list    = substr($age_list   , 0, strlen($age_list) - 1);
        $template = $this->getFormat();
        $json = sprintf($template, $day_list, $weight_list, $bmi_list, $bf_list, $sm_list, $mtb_list, $vf_list, $age_list);

        return $json;
    }

    public function getFormat () {
        return '{
            "type": "line",
            "background-color": "#003849",
            "title": {
                "y": "7px",
                "text": "TOMAnalytics",
                "background-color": "#003849",
                "font-size": "24px",
                "font-color": "white",
                "height": "25px"
            },
            "legend": {
                "layout": "float",
                "background-color": "none",
                "border-width": 0,
                "shadow": 0,
                "width":"80%%",
                "text-align":"middle",
                "x":"15%%",
                "y":"10%%",
                "item": {
                    "font-color": "#f6f7f8",
                    "font-size": "14px"
                }
            },
            "plotarea": {
                "margin": "20%% 20%% 10%% 4%%",
                "background-color": "#003849"
            },
            "crosshair-x":{},
            "plot": {
                "valueBox": {
                    "type": "all",
                    "placement": "top"
                }
            },
            "scale-x": {
                "label":{
                    "color":"#CCCCCC",
                    "text":"2015/June",
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": [%s]
            },
            "scale-y": {
                "label": {
                    "text": "weight",
                    "color":"#CCCCCC",
                    "offset-x": "4px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "line-color": "#f6f7f8",
                "values": "68:73:1"
            },
            "scale-y-2": {
                "label": {
                    "text": "BMI",
                    "color":"#CCCCCC",
                    "offset-x": "-10px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "23.5:24.5:0.2"
            },
            "scale-y-3": {
                "label": {
                    "text": "body fat",
                    "color":"#CCCCCC",
                    "offset-x": "-4px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "17:23:1"
            },
            "scale-y-4": {
                "label": {
                    "text": "skeletal muscles",
                    "color":"#CCCCCC",
                    "offset-x": "-4px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "35:40:1"
            },
            "scale-y-5": {
                "label": {
                    "text": "metabolism",
                    "color":"#CCCCCC",
                    "offset-x": "-10px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "1600:1700:20"
            },
            "scale-y-6": {
                "label": {
                    "text": "visceral fat",
                    "color":"#CCCCCC",
                    "offset-x": "-10px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "5:10:1"
            },
            "scale-y-7": {
                "label": {
                    "text": "body age",
                    "color":"#CCCCCC",
                    "offset-x": "-4px"
                },
                "line-color": "#f6f7f8",
                "tick": {
                    "line-color": "#f6f7f8"
                },
                "guide": {
                    "line-color": "#f6f7f8"
                },
                "item": {
                    "font-color": "#f6f7f8"
                },
                "values": "25:35:1"
            },
            "series": [
                {
                    "text": "weight",
                    "line-width": "2px",
                    "line-color": "#daca4d",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#daca4d",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#fafa9f"
                    },
                    "marker": {
                        "background-color": "#daca4d",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#fafa9f"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y"
                },
                {
                    "text": "BMI",
                    "line-width": "2px",
                    "line-color": "#777790",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#777790",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#dfdbf1"
                    },
                    "marker": {
                        "background-color": "#777790",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#dfdbf1"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-2"
                },
                {
                    "text": "body fat",
                    "line-width": "2px",
                    "line-color": "#009872",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#009872",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69f2d0"
                    },
                    "marker": {
                        "background-color": "#009872",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69f2d0"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-3"
                },
                {
                    "text": "skeletal muscles",
                    "line-width": "2px",
                    "line-color": "#0098d9",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#0098d9",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69f2f6"
                    },
                    "marker": {
                        "background-color": "#0098d9",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69f2f6"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-4"
                },
                {
                    "text": "metabolism",
                    "line-width": "2px",
                    "line-color": "#da534d",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#da534d",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#faa39f"
                    },
                    "marker": {
                        "background-color": "#da534d",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#faa39f"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-5"
                },
                {
                    "text": "visceral fat",
                    "line-width": "2px",
                    "line-color": "#007790",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#007790",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69dbf1"
                    },
                    "marker": {
                        "background-color": "#007790",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#69dbf1"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-6"
                },
                {
                    "text": "body age",
                    "line-width": "2px",
                    "line-color": "#767627",
                    "legend-marker": {
                        "type": "circle",
                        "size": 5,
                        "background-color": "#767627",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#868648"
                    },
                    "marker": {
                        "background-color": "#767627",
                        "border-width": 1,
                        "shadow": 0,
                        "border-color": "#868648"
                    },
                    "values": [%s],
                    "scales":"scale-x,scale-y-7"
                }
            ]
        }';
    }
}

$data_obj = new Analize();
$data_obj->main();
