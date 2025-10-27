<?php

######################################################## 初始化常量 ######################################################
const SUIT_COLS_NUM = 29; // 套装列数
const RECOMEND_COLS_NUM = 25; // 职业强散/推荐列数
const MIN_STEP = 1;
const MAX_STEP = 9;
const MIN_PROGRESS = 01;
const MAX_PROGRESS = 99;
const MIN_NUMBER = 001;
const MAX_NUMBER = 999;
const OPTIONS_MAP = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6];
const QUESTION_IMAGE = 'https://img-cos.colg.cn/activity/fog_deity_raid/image/step_{step}_question/{question_id}.jpg/ori_jpg';
const ERROR_PREFIX = "<span style='color: red;'>";
const ERROR_SUFFIX = '</span>';
const STUDY_IMAGE = 'https://img-cos.colg.cn/activity/fog_deity_raid/image/study/step_{step}{progress}.jpg/ori_jpg';
const STUDY_VIDEO = 'https://img-cos.colg.cn/activity/fog_deity_raid/video/study/step_{step}{progress}.mp4';

######################################################## 开始脚本 #######################################################

set_time_limit(0);

ini_set('memory_limit', '1024M');//设置内存

# 设置脚本开始时间
$startTime = microtime(true);

# 计算内存使用情况
$startMemory = memory_get_usage();

###################################################### 套装数据解析 ########################################################

# 初始化套装数据的csv文档名称
$filePath = './装备分类-装备套装及排序.csv';

# 解析职业map
$roleJobMap = build_role_job_map($filePath);

$suit = build_suit_equipment_data($filePath, $roleJobMap);

################################################### 散件数据解析 ####################################################

# 初始化套装数据的csv文档名称
$filePath = './装备分类-职业强散.csv';

# 解析职业map
//$roleJobMap = build_role_job_map($filePath);

$part = build_part_equipment_data($filePath, $roleJobMap);

################################################### 输出进度json文档 ####################################################

$outputFile = './equipment_data.json';

if (file_exists($outputFile)) {
    @unlink($outputFile);
}
$output = json_encode($suit['suitSortData'], JSON_UNESCAPED_UNICODE);
$output .= "\r\n\r\n";
$output .= json_encode($suit['suitData'], JSON_UNESCAPED_UNICODE);
$output .= "\r\n\r\n";
$output .= json_encode($suit['equipData'], JSON_UNESCAPED_UNICODE);
$output .= "\r\n\r\n";
$output .= json_encode($part['partSortData'], JSON_UNESCAPED_UNICODE);
$output .= "\r\n\r\n";
$output .= json_encode($part['partData'], JSON_UNESCAPED_UNICODE);

file_put_contents($outputFile, $output);

# 计算内存使用情况
$endMemory = memory_get_usage();
$logStr = '使用内存：' . round(($endMemory - $startMemory) / 1024 / 1024, 2) . ' M';
returnError($logStr);

######################################################## 函数 ##########################################################

/**
 * 解析roleJobMap
 * @param $filePath
 * @return array|false
 */
function build_role_job_map($filePath) {
    if (!file_exists($filePath)) {
        returnError("【{$filePath}】文档不存在，请将csv文件放在当前脚本所在目录后，执行...\r\n");
    }

    # 读取csv文件数据
    $list = load_csv_file_data($filePath);

    # 解析职业套装数据
    $roleJobTitleMap = [];
    $roleJobIdMap = [];
    foreach ($list as $key => $item) {
        $item = explode(',', $item);
        # 第一行为标题，略过
        if ($key == 0) {
            continue;
        } elseif ($key == 1) {
            # 解析职业数据29=AD
            $endCol = count($item) - 1;
            for ($i = 6; $i <= $endCol; $i++) {
                $roleJobTitleMap[] = $item[$i];
                $roleJobTitleMap[] = $item[$i] . '-一觉';
            }
        } elseif ($key == 2) {
            # 解析职业数据
            for ($i = 6; $i <= $endCol; $i++) {
//                $roleMapIdArr = explode('_', $item[$i]);
//                $roleMapIdArr[2] = 1;
//                $roleMapId = implode('_', $roleMapIdArr);
                $roleJobIdMap[] = $item[$i];    // $roleMapId;
                $roleJobIdMap[] = build_role_job($item[$i]);    // $roleMapId;
            }
        }
    }
    return array_combine($roleJobIdMap, $roleJobTitleMap);
}

/**
 * 构建套装数据
 * @param $filePath
 * @param $roleJobMap
 * @return array[]
 */
function build_suit_equipment_data($filePath, $roleJobMap) {
    if (!file_exists($filePath)) {
        returnError("【{$filePath}】文档不存在，请将csv文件放在当前脚本所在目录后，执行...\r\n");
    }

    # 读取csv文件数据
    $list = load_csv_file_data($filePath);

    # 将csv每行的item拆分成数组
    $suitData = [];
    $equipData = [];
    foreach ($list as $key => &$datum) {
        $datum = explode(',', $datum);
    }

    foreach ($list as $key => $item) {
        if ($key < 3) {
            if ($key == 1) {
                $endCol = count($item) - 1;
            }
            continue;
        }

        $equipName = $item[0];
        $equipPos = $item[1];
        $equipId = $item[2];
        $equipSuitName = $item[3];
        $equipSuitId = $item[4];
        $equipSuitNum = $item[5];

        $suitData[$equipSuitId]['suitId'] = $equipSuitId;
        $suitData[$equipSuitId]['suitName'] = $equipSuitName;
        $suitData[$equipSuitId]['isComplete'] = 0;
        $suitData[$equipSuitId]['total'] = $equipSuitNum;
        $suitData[$equipSuitId]['level'] = (strpos($equipSuitName, '浩海') === false) ? 50 : 55;
        $suitData[$equipSuitId]['count'] = 0;
//        $suitData[$equipSuitId]['tmpl'][$equipId]['equipId'] = $equipId;
//        $suitData[$equipSuitId]['tmpl'][$equipId]['equipPos'] = $equipPos;
//        $suitData[$equipSuitId]['tmpl'][$equipId]['equipName'] = $equipName;
        $suitData[$equipSuitId]['list'][$equipId] = [];

        $equipData[$equipId]['equipId'] = $equipId;
        $equipData[$equipId]['equipPos'] = $equipPos;
        $equipData[$equipId]['equipName'] = $equipName;
        $equipData[$equipId]['suitId'] = $equipSuitId;
        $equipData[$equipId]['suitName'] = $equipSuitName;
        $equipData[$equipId]['total'] = $equipSuitNum;

        for ($i = 6; $i <= $endCol; $i++) {
            $sort = $item[$i] ? intval($item[$i]) : 99;
            # 转职职业ID
            $roleId = $list[2][$i];
            if (!isset($suitData[$equipSuitId]['suitSort'][$roleId])) {
                $suitData[$equipSuitId]['suitSort'][$roleId] = $sort;
            }
            # 一觉角色ID
            $roleId = build_role_job($list[2][$i]);
            if (!isset($suitData[$equipSuitId]['suitSort'][$roleId])) {
                $suitData[$equipSuitId]['suitSort'][$roleId] = $sort;
            }
        }
    }

    $suitSortData = [];
    foreach ($roleJobMap as $roleJob => $roleJobTitle) {
        foreach ($suitData as $suitId => $suitDatum) {
            $suitSortData[$roleJob][$suitId] = $suitDatum['suitSort'][$roleJob];
        }
    }

//    foreach ($suitData as $suitId => $suitDatum) {
//        foreach ($suitDatum['suitSort'] as $roleJob => $sort) {
//            $suitSortData[$roleJob][$suitId] = $sort;
//        }
//    }

    return ['suitSortData' => $suitSortData, 'suitData' => $suitData, 'equipData' => $equipData];
}

/**
 * 构建散件数据
 * @param $filePath
 * @param $roleJobMap
 * @return array[]
 */
function build_part_equipment_data($filePath, $roleJobMap) {
    if (!file_exists($filePath)) {
        returnError("【{$filePath}】文档不存在，请将csv文件放在当前脚本所在目录后，执行...\r\n");
    }

    # 读取csv文件数据
    $list = load_csv_file_data($filePath);

    # 将csv每行的item拆分成数组
    $partData = [];
    foreach ($list as $key => &$datum) {
        $datum = explode(',', $datum);
    }

    foreach ($list as $key => $item) {
        if ($key < 3) {
            if ($key == 1) {
                $endCol = count($item) - 1;
            }
            continue;
        }

        $equipId = $item[0];
        $equipName = $item[1];
        $equipType = $item[2];

        $partData[$equipId]['equipId'] = $equipId;
        $partData[$equipId]['equipName'] = $equipName;
        $partData[$equipId]['equipType'] = $equipType;

        // 25=Z
        for ($i = 3; $i <= $endCol; $i++) {
            $sort = $item[$i] ?: 0;
            # 转职职业ID
            $roleId = $list[2][$i];
            if (!isset($partData[$equipId]['suitSort'][$roleId])) {
                $partData[$equipId]['suitSort'][$roleId] = $sort;
            }
            # 一觉职业ID
            $roleId = build_role_job($list[2][$i]);
            if (!isset($partData[$equipId]['suitSort'][$roleId])) {
                $partData[$equipId]['suitSort'][$roleId] = $sort;
            }
        }
    }

    $partSortData = [];
    foreach ($roleJobMap as $roleJob => $roleJobTitle) {
        foreach ($partData as $equipId => $partDatum) {
            $partSortData[$roleJob][$equipId] = $partDatum['suitSort'][$roleJob];
        }
    }

//    foreach ($partData as $equipId => $equipDatum) {
//        foreach ($equipDatum['suitSort'] as $roleJob => $sort) {
//            $partSortData[$roleJob][$equipId] = $sort;
//        }
//    }

    return ['partSortData' => $partSortData, 'partData' => $partData];
}

/**`
 * 读取csv文件数据
 * @param $filePath
 * @return array
 */
function load_csv_file_data($filePath)
{
    # 读取csv文档内容
    $csvData = trim(file_get_contents($filePath));
    if (empty($csvData)) {
        returnError("【{$filePath}】文档内容为空，error：210011");
    }

    # 检测字符的编码
    $encode = mb_detect_encoding($csvData, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
    if ($encode != 'UTF-8') {
        $csvData = iconv($encode, 'utf-8', $csvData);
    }

    # 特殊字符替换
    $csvData = str_replace('・', '·', $csvData);

    if ($csvData == '') {
        returnError("【{$filePath}】文档内容为空，error：210012");
    }

    # 根据换行符拆分数据行
    return explode(PHP_EOL, $csvData);
}

/**
 * 構建角色roleJob
 * @param $roleJob
 * @return string
 */
function build_role_job($roleJob) {
    $roleMapIdArr = explode('_', $roleJob);
    $roleMapIdArr[2] = 1;
    return implode('_', $roleMapIdArr);
}

function returnError($msg) {
    exit(iconv('utf-8', 'gb2312', $msg));
}
