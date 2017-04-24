<?php

/**
 * http://www.weirdog.com/blog/php/php-xml-previsions-meteo.html
 *
 */

class WdWeather
{
    var $markup_contents = '([^<]+)';
    
    function getCityCode($search)
    {
        #
        # we create a nice looking URL
        #
        
        $search = explode(',', $search);
        $search = array_map('urlencode', $search);
        $search = implode(',+', $search);
        
        #
        # ask the service about the location
        #
    
        $xml = @file_get_contents
        (
            'http://xml.weather.com/search/search?where=' . $search
        );
        
        #
        # try to get the first result
        #
        
        if (!preg_match('#loc id="([^"]+)#', $xml, $matches))
        {
            return;
        }
        
        return $matches[1];
    }

    function getWeather($city_code, $days)
    {
        $xml = @file_get_contents
        (
            'http://xml.weather.com/weather/local/' . $city_code .
            '?cc=*&unit=s&dayf=' . $days
        );
        
        if (!$xml)
        {
            return;
        }
        
        return $this->parseDays($xml);
    }
    
    function parseDays($xml)
    {
        $days = array();
        
        #
        # split xml by days
        #

        $parts = preg_split('#<day d=[^>]+>#', $xml);

        array_shift($parts);

        $mc = $this->markup_contents;

        foreach ($parts as $xml)
        {
            $days[] = $this->parseDay($xml);
        }
        
        return $days;
    }

    function parseDay($xml)
    {
        $mc = $this->markup_contents;
    
        preg_match
        (
            '#' .
            
            '<hi>' . $mc . '</hi>\s*' .
            '<low>' . $mc . '</low>\s*' .
            '<sunr>' . $mc . '</sunr>\s*' .
            '<suns>' . $mc . '</suns>\s*' .
            '<part p="d">(.*)</part>\s*' .
            '<part p="n">(.*)</part>' .
            
            '#ms',
            
            $xml, $matches
        );
        
        array_shift($matches);
                    
        #
        # parse day periods (day / night)
        #
        
        $matches[4] = $this->parseDayPeriod($matches[4]);
        $matches[5] = $this->parseDayPeriod($matches[5]);
        
        #
        #
        #
        
        if(count($matches) !== 6){
            return array('hi', 'low', 'sunr', 'suns', 'day', 'night');
        }
        
        return array_combine
        (
            array('hi', 'low', 'sunr', 'suns', 'day', 'night'), $matches
        );
    }
    
    function parseDayPeriod($xml)
    {
        $mc = $this->markup_contents;

        preg_match
        (
            '#' .
            
            '<icon>' . $mc . '</icon>\s*' .
            '<t>' . $mc . '</t>\s*' .
            '<wind>(.*)</wind>\s*' .
            '<bt>' . $mc . '</bt>\s*' .
            '<ppcp>' . $mc . '</ppcp>\s*' .
            '<hmid>' . $mc . '</hmid>' .
            
            '#ms',
            
            $xml, $matches
        );
        
        array_shift($matches);
        
        $matches[2] = $this->parseWind($matches[2]);
        
        if(count($matches) !== 6){
           return  array('icon', 't', 'wind', 'bt', 'ppcp', 'hmid');
        } 
        
        return array_combine
        (
            array('icon', 't', 'wind', 'bt', 'ppcp', 'hmid'), $matches
        );
    }
    
    function parseWind($xml)
    {
        $mc = $this->markup_contents;

        preg_match
        (
            '#' .
            
            '<s>' . $mc . '</s>\s*' .
            '<gust>' . $mc . '</gust>\s*' .
            '<d>' . $mc . '</d>\s*' .
            '<t>' . $mc . '</t\s*' .
            
            '#ms',
            
            $xml, $matches
        );
        
        array_shift($matches);
        
        if(count($matches) !== 4){
            return array('s', 'gust', 'd', 't');
        }
        
        return array_combine
        (
            array('s', 'gust', 'd', 't'), $matches
        );
    }

    static function toCelsius($f)
    {
        if (!is_numeric($f))
        {
            return $f;
        }
    
        return round(($f-32) * 5 / 9);
    }
    
    static function to24H($time)
    {
        preg_match('#(\d+)\:(\d+)\s+(AM|PM)?#', $time, $matches);
        
        if ($matches[3] == 'PM')
        {
            $matches[1] += 12;
        }

        return $matches[1] . ':' . $matches[2];
    }
}
?>