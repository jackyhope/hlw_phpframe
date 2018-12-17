<?php

/**
 * 类成员常量定义
 *
 */
class hlw_conf_constant {

    const INTRODUCE_LENGTH = 30;
    const REDIS_HOST = '192.168.3.201';
    const REDIS_PORT = 6379;
    const REDIS_PASSWORD = '';
    const MQ_HOST = '192.168.3.201';
    const MQ_PORT = '5672';
    const MQ_USERNAME = 'admin';
    const MQ_PASSWORD = 'admin';
    const MQ_VHOST = '/';

    //从事行业
    static $huilie_to_oa_hy = [
        1 => '040', //互联网·游戏·软件
        11 => '330', //能源·化工·环保
        10 => '270', //制药·医疗
        9 => '250', //交通·贸易·物流
        8 => '070', //广告·传媒·教育·文化
        7 => '120', //服务·外包·中介
        6 => '350', //汽车·机械·制造,
        5 => '190', //消费品
        4 => '150', //金融
        3 => '080', //房地产·建筑·物业
        2 => '050', // 电子·通信·硬件
        12 => '390'//政府·农林牧渔
    ];
    //企业规模
    static $huilie_to_oa_mun = [
        27 => '10人以下',
        28 => '10-50人',
        29 => '50-200人',
        30 => '200-500人',
        31 => '500-1000人',
        32 => '1000人以上'
    ];
    //到岗时间
    static $huilie_to_oa_report = [
        54 => '不限',
        57 => '1周以内',
        58 => '2周以内',
        59 => '3周以内',
        60 => '1个月之内'
    ];
    //工作经验
    static $huilie_to_oa_exp = [
        12 => [0, 1], //应届毕业生
        13 => [1, 2], //1年以上
        14 => [2, 3], //2年以上                                                         
        15 => [3, 5], //3年以上                                                             
        16 => [5, 8], //5年以上
        17 => [8, 10], //8年以上
        18 => [10, -1]//10年以上                                                        
    ];
    //年龄要求
    static $huilie_to_oa_age = [
        85 => [18, 25], //18-25岁
        86 => [25, 35], //35岁以下
        87 => [35, -1]//35岁以上
    ];
    //性别要求
    static $huilie_to_oa_sex = [
        3 => '不限',
        1 => '男',
        2 => '女'
    ];
    //教育程度
    static $huilie_to_oa_edu = [
        66 => '大专',
        67 => '本科',
        68 => '硕士',
        69 => 'MBA',
        70 => 'EMBA',
        71 => '博士',
        65 => '博士后'
    ];

}
