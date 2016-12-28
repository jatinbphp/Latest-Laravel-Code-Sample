<?php

namespace App\Helpers;

class CommonHelpers{
	
	public function generate_timezone_list()
	{
		static $regions = array(			
			\DateTimeZone::AMERICA,			
		);	 
		$timezones = array();
		foreach( $regions as $region )
		{
			$timezones = array_merge( $timezones, \DateTimeZone::listIdentifiers( $region ) );
		}
	 
		$timezone_offsets = array();
		foreach( $timezones as $timezone )
		{
			$tz = new \DateTimeZone($timezone);
			$timezone_offsets[$timezone] = $tz->getOffset(new \DateTime);
		}
	 
		// sort timezone by timezone name
		ksort($timezone_offsets);
	 
		$timezone_list = array();
		
		unset($timezone_offsets);
		$timezone_offsets['America/Creston'] = -25200;
		$timezone_offsets['America/Boise'] = -21600;
		$timezone_offsets['America/Bogota'] = -18000;
		$timezone_offsets['America/Caracas'] = -14400;
		foreach( $timezone_offsets as $timezone => $offset )
		{
			
			//if($timezone == 'America/Belize' || $timezone == 'America/Atikokan' || $timezone == 'America/Boise' || $timezone == 'America/Chihuahua' || $timezone == 'America/Costa_Rica' || $timezone == 'America/Denver' || $timezone == 'America/Edmonton' || $timezone == 'America/El_Salvador' || $timezone == 'America/Guatemala' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '' || $timezone == '')
			$offset_prefix = $offset < 0 ? '-' : '+';
			$offset_formatted = gmdate( 'H:i', abs($offset) );
	 
			$pretty_offset = "UTC${offset_prefix}${offset_formatted}";
		   
			$t = new \DateTimeZone($timezone);
			$c = new \DateTime(null, $t);
			$current_time = $c->format('g:i A');
	 
			
				if($timezone=='America/Creston')
					$name = 'Pacific Time';
				if($timezone=='America/Boise')
					$name = 'Mountain Time';
				if($timezone=='America/Bogota')
					$name = 'Central Time';
				if($timezone=='America/Caracas')
					$name = 'Eastern Time Time';
				
					$timezone_list[$timezone] = "(${pretty_offset}) $name - $current_time";
		}
	 
		return $timezone_list;
	}
	
}

