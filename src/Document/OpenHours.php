<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation\Expose;

/** @MongoDB\EmbeddedDocument */
class OpenHours
{
    protected $days = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];

    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Monday;

    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Tuesday;
    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Wednesday;
    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Thursday;
    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Friday;
    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Saturday;
    /**
     * @Expose
     * @MongoDB\EmbedOne(targetDocument="App\Document\DailyTimeSlot") */
    private $Sunday;

    public function __construct($openHours = null)
    {
        if ($openHours && is_array($openHours)) {
            foreach ($openHours as $day => $timeSlotString) {
                $slot1start = null;
                $slot1end = null;
                $slot2start = null;
                $slot2end = null;
                $slots = explode(',', $timeSlotString);
                if (count($slots) > 0) {
                    list($slot1start, $slot1end) = $this->buildSlotsFrom($slots[0]);
                }
                if (2 == count($slots)) {
                    list($slot2start, $slot2end) = $this->buildSlotsFrom($slots[1]);
                }
                $dailySlot = new DailyTimeSlot($slot1start, $slot1end, $slot2start, $slot2end);
                $method = 'set'.$this->days[$day];
                $this->$method($dailySlot);
            }
        }
    }

    /**
     * Defines time slots from OpenStreetMap opening_hours=* syntax
     * @throws Exception When OSM syntax is not supported
     */
    public function buildFromOsm($osmOh)
    {
        $osmOh = trim($osmOh);

        // Supported regex patterns
        $daysRgx = '(Mo|Tu|We|Th|Fr|Sa|Su)';
        $daysRangeRgx = '('.$daysRgx.'-'.$daysRgx.')';
        $dayOrRangeRgx = '('.$daysRangeRgx.'|'.$daysRgx.')';
        $daysListRgx = '('.$dayOrRangeRgx.'(,\s*'.$dayOrRangeRgx.')*)';
        $hoursRgx = '\d{1,2}\:\d{2}';
        $hoursRangeRgx = '('.$hoursRgx.'-'.$hoursRgx.')';
        $hoursListRgx = '('.$hoursRangeRgx.'(,\s*'.$hoursRangeRgx.')?)';
        $singleRuleRgx = '(('.$daysListRgx.'\s+)?'.$hoursListRgx.')';
        $severalRulesRgx = '/^'.$singleRuleRgx.'(\s*;\s*'.$singleRuleRgx.')*$/i';

        // 24/24, 7/7 case
        if($osmOh == '24/7') {
            $this->setMonday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setTuesday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setWednesday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setThursday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setFriday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setSaturday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
            $this->setSunday(new DailyTimeSlot(...$this->buildSlotsFrom('00:00-00:00')));
        }
        // Classic list of rules case
        else if (preg_match($severalRulesRgx, $osmOh)) {
            // Split each rules to parse them one by one
            $rules = array_map(function($s) { return trim($s); }, explode(";", $osmOh));

            foreach($rules as $rule) {
                preg_match('/^'.$daysListRgx.'/i', $rule, $dp);
                preg_match('/'.$hoursListRgx.'$/', $rule, $hp);

                $ruleDays = [];
                $ruleHours = [];

                // Read list of days
                if(isset($dp[0])) {
                    // Split by comma to have either single day or day range
                    $dl = array_map(function($s) { return trim($s); }, explode(",", $dp[0]));

                    foreach($dl as $singleDay) {
                        // Single day
                        if(preg_match('/^'.$daysRgx.'$/i', $singleDay)) {
                            array_push($ruleDays, strtoupper($singleDay[0]).strtolower($singleDay[1]));
                        }
                        // Days range
                        else if(preg_match('/^'.$daysRangeRgx.'$/i', $singleDay)) {
                            $ruleDaysParts = array_map(function($s) { return strtoupper($s[0]).strtolower($s[1]); }, explode("-", $singleDay));
                            $startDayId = array_search($ruleDaysParts[0], array_keys($this->days));
                            $endDayId = array_search($ruleDaysParts[1], array_keys($this->days));

                            if($startDayId < $endDayId) {
                                for($i = $startDayId; $i <= $endDayId; $i++) {
                                    array_push($ruleDays, array_keys($this->days)[$i]);
                                }
                            }
                            else if($startDayId > $endDayId) {
                                for($i = $startDayId; $i < count($this->days); $i++) {
                                    array_push($ruleDays, array_keys($this->days)[$i]);
                                }
                                for($i = 0; $i <= $endDayId; $i++) {
                                    array_push($ruleDays, array_keys($this->days)[$i]);
                                }
                            }
                            else {
                                array_push($ruleDays, $ruleDaysParts[0]);
                            }
                        }
                    }
                }
                else {
                    $ruleDays = array_keys($this->days);
                }

                // Read list of hours
                if(isset($hp[0])) {
                    // Split by comma to get each hours range
                    $hl = array_map(function($s) { return trim($s); }, explode(",", $hp[0]));

                    foreach($hl as $singleHourRange) {
                        // Merge this range with existing list
                        $ruleHours = array_merge($ruleHours, $this->buildSlotsFrom($singleHourRange));
                    }

                    // Apply hours ranges to each concerned day
                    foreach($ruleDays as $rd) {
                        $method = 'set'.$this->days[$rd];
                        $this->$method(new DailyTimeSlot(...$ruleHours));
                    }
                }
            }
        }
        // If no format recognized
        else {
            throw new \Exception('Syntaxe OSM pour les horaires d\'ouvertures non-reconnue : '.$osmOh);
        }
    }

    private function buildSlotsFrom($string)
    {
        $times = explode('-', $string);
        if (count($times) != 2) return [null, null];
        if ($times[0] == "" || $times[1] == "") return [null, null];
        $start = date_create_from_format('H:i', $times[0]);
        $end = date_create_from_format('H:i', $times[1]);
        if (!$start || !$end) return [null, null];
        return [$start, $end];
    }

    public function toJson()
    {
        $result = '{';
        if ($this->Monday) {
            $result .= '"Mo":'.$this->Monday->toJson().',';
        }
        if ($this->Tuesday) {
            $result .= '"Tu":'.$this->Tuesday->toJson().',';
        }
        if ($this->Wednesday) {
            $result .= '"We":'.$this->Wednesday->toJson().',';
        }
        if ($this->Thursday) {
            $result .= '"Th":'.$this->Thursday->toJson().',';
        }
        if ($this->Friday) {
            $result .= '"Fr":'.$this->Friday->toJson().',';
        }
        if ($this->Saturday) {
            $result .= '"Sa":'.$this->Saturday->toJson().',';
        }
        if ($this->Sunday) {
            $result .= '"Su":'.$this->Sunday->toJson().',';
        }
        $result = rtrim($result, ',');
        $result .= '}';

        return $result;
    }

    public function toOsm()
    {
        $output = null;
        $commonDays = [];

        // Group hours per day ranges
        foreach($this->days as $dayKey => $dayStr) {
            // If day is defined
            if($this->$dayStr) {
                // Check if its hours match with an existing range of days
                $matchFound = false;
                foreach($commonDays as $hours => $hoursDays) {
                    if($hours == $this->$dayStr->toJson()) {
                        $matchFound = true;
                        array_push($commonDays[$hours], $dayKey);
                    }
                }

                // If not, add as a new range
                if(!$matchFound && $this->$dayStr->toJson() != '""') {
                    $commonDays[$this->$dayStr->toJson()] = [$dayKey];
                }
            }
        }

        // Empty list
        if(count(array_keys($commonDays)) == 0) {
            $output = '';
        }
        // Same hours all week
        else if(count(array_keys($commonDays)) == 1 && count(array_keys($commonDays[array_key_first($commonDays)])) == 7) {
            $output = substr(array_key_first($commonDays), 1, -1);

            // Special case when it's open everyday, all-day long
            if($output == '00:00-00:00') { $output = '24/7'; }
        }
        // Other cases
        else {
            $output = '';

            // For each range of days having same hours
            foreach($commonDays as $hours => $daysList) {
                // Add separator between each rule
                if(strlen($output) > 0) {
                    $output .= '; ';
                }

                // Compute day range string
                // Single day
                if(count($daysList) == 1) {
                    $output .= $daysList[0];
                }
                // Several days
                else {
                    // Group following days
                    $groupedDays = [ [ $daysList[0] ] ];
                    for($i=1; $i < count($daysList); $i++) {
                        $prevDayId = array_search($daysList[$i-1], array_keys($this->days));
                        $currDayId = array_search($daysList[$i], array_keys($this->days));

                        if($prevDayId == $currDayId - 1) {
                            array_push($groupedDays[array_key_last($groupedDays)], $daysList[$i]);
                        }
                        else {
                            array_push($groupedDays, [$daysList[$i]]);
                        }
                    }

                    // Eventually merge first and last group if sunday -> monday
                    if(count($groupedDays) > 1 && $groupedDays[0][0] == 'Mo' && $groupedDays[array_key_last($groupedDays)][array_key_last($groupedDays[array_key_last($groupedDays)])] == 'Su') {
                        $lastRange = array_pop($groupedDays);
                        $groupedDays[0] = array_merge($lastRange, $groupedDays[0]);
                    }

                    // Display list of days
                    $output .= implode(',', array_map(
                        function($gd) { return count($gd) <= 2 ? implode(',', $gd) : $gd[0].'-'.$gd[array_key_last($gd)]; },
                        $groupedDays
                    ));
                }

                $output .= ' '.($hours == '"00:00-00:00"' ? '00:00-24:00' : substr($hours, 1, -1));
            }
        }

        return $output;
    }

    public function getMonday()
    {
        return $this->Monday;
    }

    public function getTuesday()
    {
        return $this->Tuesday;
    }

    public function getWednesday()
    {
        return $this->Wednesday;
    }

    public function getThursday()
    {
        return $this->Thursday;
    }

    public function getFriday()
    {
        return $this->Friday;
    }

    public function getSaturday()
    {
        return $this->Saturday;
    }

    public function getSunday()
    {
        return $this->Sunday;
    }

    // setters
    public function setMonday($dailyTimeSlot)
    {
        $this->Monday = $dailyTimeSlot;

        return $this;
    }

    public function setTuesday($dailyTimeSlot)
    {
        $this->Tuesday = $dailyTimeSlot;

        return $this;
    }

    public function setWednesday($dailyTimeSlot)
    {
        $this->Wednesday = $dailyTimeSlot;

        return $this;
    }

    public function setThursday($dailyTimeSlot)
    {
        $this->Thursday = $dailyTimeSlot;

        return $this;
    }

    public function setFriday($dailyTimeSlot)
    {
        $this->Friday = $dailyTimeSlot;

        return $this;
    }

    public function setSaturday($dailyTimeSlot)
    {
        $this->Saturday = $dailyTimeSlot;

        return $this;
    }

    public function setSunday($dailyTimeSlot)
    {
        $this->Sunday = $dailyTimeSlot;

        return $this;
    }

    /*public function __construct()
    {
    }

    public setDailyTimeSlot($day,$plage1,$plage2)
    {
        $this->$days[$day] = new DailyTimeSlot($plage1,$plage2);
    }

    public getDailyTimeSlot($day)
    {
        return $this->$days[$day];
    }*/
}
