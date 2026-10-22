<?php

namespace Core\Model\Date;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Timezone
{

    protected static mixed $_instance = null;

    protected mixed $_timezones = null;

    public function __construct()
    {
        $this->_timezones = ['Africa/Cairo' => '埃及标准时间', 'Africa/Casablanca' => '摩洛哥标准时间', 'Africa/Johannesburg' => '南非标准时间', 'Africa/Lagos' => '中非标准时间', 'Africa/Nairobi' => '非洲标准时间', 'Africa/Windhoek' => '纳米比亚标准时间', 'America/Anchorage' => '阿拉斯加标准时间', 'America/Bogota' => 'SA太平洋标准时间', 'America/Buenos_Aires' => '阿根廷标准时间', 'America/Caracas' => '委内瑞拉标准时间', 'America/Chicago' => '中部标准时间', 'America/Chihuahua' => '墨西哥标准时间2', 'America/Denver' => '美国丹佛山地标准时间', 'America/Godthab' => '格陵兰标准时间', 'America/Guatemala' => '中美洲标准时间', 'America/Halifax' => '大西洋标准时间', 'America/La_Paz' => 'SA西部标准时间', 'America/Los_Angeles' => '太平洋标准时间', 'America/Manaus' => '巴西中部标准时间', 'America/Mexico_City' => '中部标准时间', 'America/Montevideo' => '蒙得维的亚标准时间', 'America/New_York' => '东部标准时间', 'America/Phoenix' => '美洲菲尼克斯标准时间', 'America/Regina' => '加拿大中部标准时间', 'America/Santiago' => '太平洋SA标准时间', 'America/Sao_Paulo' => '东南美洲标准时间', 'America/St_Johns' => '纽芬兰标准时间', 'America/Tijuana' => '太平洋标准时间', 'Asia/Amman' => '约旦标准时间', 'Asia/Baghdad' => '阿拉伯标准时间', 'Asia/Baku' => '阿塞拜疆标准时间', 'Asia/Bangkok' => '东南亚标准时间', 'Asia/Beirut' => '中东标准时间', 'Asia/Calcutta' => '印度标准时间', 'Asia/Colombo' => '斯里兰卡标准时间', 'Asia/Dhaka' => '中亚标准时间', 'Asia/Dubai' => '阿拉伯标准时间', 'Asia/Irkutsk' => '北亚东部标准时间', 'Asia/Jerusalem' => '以色列标准时间', 'Asia/Kabul' => '阿富汗标准时间', 'Asia/Karachi' => '巴基斯坦标准时间', 'Asia/Katmandu' => '尼泊尔标准时间', 'Asia/Krasnoyarsk' => '北亚标准时间', 'Asia/Novosibirsk' => '中亚标准时间', 'Asia/Rangoon' => '缅甸标准时间', 'Asia/Riyadh' => '阿拉伯标准时间', 'Asia/Seoul' => '韩国标准时间', 'Asia/Shanghai' => '中国标准时间', 'Asia/Singapore' => '新加坡标准时间', 'Asia/Taipei' => '台北标准时间', 'Asia/Tashkent' => '西亚标准时间', 'Asia/Tehran' => '伊朗标准时间', 'Asia/Tokyo' => '东京标准时间', 'Asia/Vladivostok' => '符拉迪沃斯托克标准时间', 'Asia/Yakutsk' => '雅库茨克标准时间', 'Asia/Yekaterinburg' => '叶卡捷琳堡标准时间', 'Asia/Yerevan' => '亚美尼亚标准时间', 'Atlantic/Azores' => '亚速尔群岛标准时间', 'Atlantic/Cape_Verde' => '佛得角标准时间', 'Atlantic/Reykjavik' => '格林尼治标准时间', 'Atlantic/South_Georgia' => '大西洋中部标准时间', 'Australia/Adelaide' => '澳大利亚阿德莱德标准时间', 'Australia/Brisbane' => '澳大利亚/布里斯班标准时间', 'Australia/Darwin' => '澳大利亚中部标准时间', 'Australia/Hobart' => '塔斯马尼亚岛标准时间', 'Australia/Perth' => '澳大利亚西部标准时间', 'Australia/Sydney' => '澳大利亚东部标准时间', 'Etc/GMT+12' => '日界线标准时间', 'Etc/GMT+3' => 'SA东部标准时间', 'Etc/GMT+5' => '美国东部标准时间', 'Etc/GMT-3' => '格鲁吉亚标准时间', 'Europe/Berlin' => '西欧标准时间', 'Europe/Budapest' => '中欧标准时间', 'Europe/Istanbul' => '土耳其标准时间', 'Europe/Kiev' => '乌克兰标准时间', 'Europe/London' => '格林威治标准时间', 'Europe/Minsk' => '东欧标准时间', 'Europe/Moscow' => '俄罗斯标准时间', 'Europe/Paris' => '罗马标准时间', 'Europe/Warsaw' => '中欧标准时间', 'Indian/Mauritius' => '毛里求斯标准时间', 'Pacific/Apia' => '萨摩亚标准时间', 'Pacific/Auckland' => '新西兰标准时间', 'Pacific/Fiji' => '斐济标准时间', 'Pacific/Guadalcanal' => '环太平洋标准时间', 'Pacific/Honolulu' => '夏威夷标准时间', 'Pacific/Port_Moresby' => '西太平洋标准时间', 'Pacific/Tongatapu' => '汤加标准时间'];
    }

    /**
     * 使用单例模式
     * @return \Core\Model\Date\Timezone
     */
    public static function singleton(): Timezone
    {
        return self::$_instance === null ? self::$_instance = new Timezone() : self::$_instance;
    }

    /**
     * @param string $timezone
     * @return array|string
     */
    public function getTimezones(?string $timezone = null): array|string
    {
        if ($timezone === null) {
            return $this->_timezones;
        }
        return isset($this->_timezones[$timezone]) ? $this->_timezones[$timezone] : null;
    }

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getAllOptions(bool $withEmpty = false): array
    {
        $options = [];
        if ($withEmpty) {
            $options[] = ['value' => '', 'label' => Ddm::getTranslate('core')->translate('使用系统默认')];
        }
        foreach ($this->getTimezones() as $timezone => $label) {
            $options[] = ['value' => $timezone, 'label' => Ddm::getTranslate('core')->translate($label) . ' (' . $timezone . ')'];
        }
        return $options;
    }
}
