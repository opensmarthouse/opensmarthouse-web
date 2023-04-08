<?php

namespace lib\core;

class devicecheck
{
    private $imageExtensions = array(
		'.jpg',
		'.jpeg',
		'.gif',
		'.png'
	);
	
	private $docExtensions = array(
		'.pdf',
		'.PDF'
    );
    
    private $optionAlarmTypes = array(
        'GENERAL',
        'SMOKE',
        'CARBON_MONOXIDE',
        'CARBON_DIOXIDE',
        'HEAT',
        'FLOOD',
        'ACCESS_CONTROL',
        'BURGLAR',
        'POWER_MANAGEMENT',
        'SYSTEM',
        'EMERGENCY',
        'CLOCK',
        'APPLIANCE',
        'HOME_HEALTH',
        'SIREN',
        'WATER_VALVE',
        'WEATHER',
        'IRRIGATION',
        'GAS',
        'PEST_CONTROL',
        'LIGHT_SENSOR',
        'WATER_QUALITY',
        'HOME_MONITORING'
    );
    
    private $channelsThatCanBeDuplicated = array(
        'config_decimal',
        'sensor_temperature',
        'switch_dimmer',
        'thermostat_setpoint'
    );

	private $channelsImplemented = array(
        'air_temperature[sensor_temperature]',
        'alarm_access',
        'alarm_battery',
        'alarm_burglar',
        'alarm_clock',
        'alarm_co',
        'alarm_co2',
        'alarm_cold',
        'alarm_combustiblegas',
        'alarm_emergency',
        'alarm_entry',
        'alarm_flood',
        'alarm_general',
        'alarm_heat',
        'alarm_humidity',
        'alarm_motion',
        'alarm_number',
        'alarm_power',
        'alarm_raw',
        'alarm_smoke',
        'alarm_system',
        'alarm_tamper',
        'alarm_watervalve',
        'barrier_position',
        'barrier_state',
        'basic_number',
        'blinds_control',
        'blinds_lamella',
        'blinds_shutter',
        'color_color',
        'color_raw',
        'color_temperature',
        'config_decimal',
        'floor_temperature[sensor_temperature]',
        'indicator_level',
        'indicator_period',
        'indicator_flash',
        'indicator_raw',
        'lock_door',
        'meter_current',
        'meter_gas_cubic_feet',
        'meter_gas_cubic_meters',
        'meter_gas_cubic_pulses',
        'meter_kvah',
        'meter_kwh',
        'meter_powerfactor',
        'meter_pulse',
        'meter_reset',
        'meter_voltage',
        'meter_water_cubic_feet',
        'meter_water_gallons',
        'meter_water_cubic_meters',
        'meter_water_pulse',
        'meter_watts',
        'notification_access_control',
        'notification_home_security',
        'notification_power_management',
        'notification_smoke_alarm',
        'notification_send',
        'notification_siren',
        'notification_system',
        'scene_number',
        'sensor_barpressure',
        'protection_local',
        'protection_rf',
        'sensor_binary',
        'sensor_co',
        'sensor_co2',
        'sensor_current',
        'sensor_direction',
        'sensor_dewpoint',
        'sensor_door',
        'sensor_flood',
        'sensor_frequency',
        'sensor_general',
        'sensor_luminance',
        'sensor_moisture',
        'sensor_particulate',
        'sensor_power',
        'sensor_rainrate',
        'sensor_report',
        'sensor_relhumidity',
        'sensor_seismicintensity',
        'sensor_smoke',
        'sensor_temperature',
        'sensor_ultraviolet',
        'sensor_velocity',
        'sensor_voltage',
        'sensor_waterflow',
        'sensor_waterpressure', 
        'sound_default_tone',
        'sound_tone_play',
        'sound_volume',
        'switch_binary',
        'switch_dimmer',
        'switch_startstop',
        'thermostat_setpoint',
        'thermostat_fanmode',
        'thermostat_fanstate',
        'thermostat_state',
        'thermostat_mode',
        'time_offset'
    );
    
    private $channelsDeprecated = array(
        'alarm_number'=>'alarm_raw',
        'notification_access_control'=>'alarm_raw'
    );

    private $response = array();

    // all public function will be available as actions
    // parameters are always $options and $name
    // $options will contain the options for the action
    // $name is the name of the action
    public function check($device)
    {
        $this->response['errors'] = false;
        $this->response['warnings'] = false;

        if(!isset($device)) {
            $this->createError('device', 'Device', 'No device data was provided.');
            return $this->response;
        }

        global $firstChannel;

        // Check the thingid
        if(isset($device->uuid) == false || strlen(trim($device->uuid)) == 0) {
            $this->createError('device.uuid', 'Device UUID', 'Value can not be empty. ');
        } else {
            if(preg_match('/[\'^£$%&*()}{@#~?><>,.|=_+¬-]/', $device->uuid)) {
                $this->createError('device.uuid', 'Device UUID', 'Value contains invalid characters.');
            }
            
            if(strlen(trim($device->uuid)) > 15) {
                $this->createError('device.uuid', 'Device UUID', 'Value is too long. Reduce to 15 characters or less.');
            }

            if (isset($device->manufacturer->uuid) && strlen($device->manufacturer->uuid) > 0) {
                if(strpos($device->uuid, $device->manufacturer->uuid) !== false) {
                    $this->createWarning('device.uuid', 'Device UUID', 'Value should not contain the manufacturer name.');
                }
            }
        }
        
        if (isset($device->manufacturer->uuid) && strlen($device->manufacturer->uuid) > 0) {
            if(strpos($device->label, $device->manufacturer->uuid) !== false) {
                $this->createWarning('device.label', 'Device Label', 'Value should not contain manufacturer name.');
            }
            else if(strpos($device->label, $device->manufacturer->label) !== false) {
                $this->createWarning('device.label', 'Device Label', 'Value should not contain manufacturer name.');
            }

            if(strpos($device->description, $device->manufacturer->uuid) !== false) {
                $this->createWarning('device.description', 'Device Description', 'Value should not contain manufacturer name.');
            }
            else if(strpos($device->description, $device->manufacturer->label) !== false) {
                $this->createWarning('device.description', 'Device Description', 'Value should not contain manufacturer name.');
            }
        } else {
            $this->createError('device.manufacturer.uuid', 'Manufacturer UUID', 'Value is not set.');
        }

        if(!isset($device->overview) || strlen($device->overview) == 0) {
            $this->createError('device.overview', 'Device Overview', 'Information is not set');
        }
        if(!isset($device->inclusion) || strlen($device->inclusion) == 0) {
            $this->createError('device.inclusion', 'Device Inclusion', 'Information is not set');
        }
        if(!isset($device->exclusion) || strlen($device->exclusion) == 0) {
            $this->createError('device.exclusion', 'Device Exclusion', 'Information is not set');
        }

        $wakeupSupported = false;
        if(isset($device->endpoints) && count($device->endpoints) > 0) {
            for($cntE = 0; $cntE < count($device->endpoints); $cntE++) {
                $endpoint = $device->endpoints[$cntE];
                for($cntC = 0; $cntC < count($endpoint->commandclass); $cntC++) {
                    $commandClass = $endpoint->commandclass[$cntC];
                    if(strpos($commandClass->commandclass_name, "WAKE") !== false) {
                        $wakeupSupported = true;
                        break;
                    }
                }
            }
        } else {
            $this->createError('device.endpoints', 'Device Endpoints', 'No endpoints are configured.');
        }
        
        if($wakeupSupported == true && isset($device->wakeup) && strlen($device->wakeup) == 0) {
            $this->createError('device.wakeup', 'Device Wakeup', 'Information is not set.');
        }

        $verComponent = floor($device->version_min);
        if($verComponent < 0 || $verComponent > 255) {
            $this->createError('device.version_min', 'Device Minimum Version','Value is invalid - value outside allowable range 0 to 255.');
        }
        $verComponent = ($device->version_min - $verComponent) * 1000;
        if($verComponent < 0 || $verComponent > 255) {
            $this->createError('device.version_min', 'Device Minimum Version', 'Value is invalid - value outside allowable range 0 to 255.');
        }

        $verComponent = floor($device->version_max);
        if($verComponent < 0 || $verComponent > 255) {
            $this->createError('device.version_max', 'Device Maximum Version',  'Value is invalid - value outside allowable range 0 to 255.');
        }
        $verComponent = ($device->version_max - $verComponent) * 1000;
        if($verComponent < 0 || $verComponent > 255) {
            $this->createError('device.version_max', 'Device Maximum Version', 'Value is invalid - value outside allowable range 0 to 255');
        }

        if($device->version_min > $device->version_max) {
            $this->createError('device.version_min', 'Device Minimum Version',  'Value is greater than maximum version.');
            $this->createError('device.version_max', 'Device Maximum Version',  'Value is less than minimum version.');
        }

        if($device->version_max > 255.255) {
            $this->createError('device.version_max', 'Device Maximum Version', 'Value is greater than maximum allowable value of 255.255.');
        }

 //       $this->createError('device.label', 'Device Maximum Version',  'Value is less than minimum version.');
 //       $this->createWarning('device.label', 'Device Maximum Version',  'Value is less than minimum version.');


        return $this->response;
    }

    private function createWarning($element, $name, $message) {
        $this->response['warnings'] = true;
        $this->createResponseElement($element, $name);
        if (!isset($this->response[$element]['warnings'])) {
            $this->response[$element]['warnings'] = array();
        }
        array_push($this->response[$element]['warnings'], $message);
    }

    private function createError($element, $name, $message) {
        $this->response['errors'] = true;
        $this->createResponseElement($element, $name);
        if (!isset($this->response[$element]['errors'])) {
            $this->response[$element]['errors'] = array();
        }
        array_push($this->response[$element]['errors'], $message);
    }

    private function createResponseElement($element, $name) {
        if (!isset($this->response[$element])) {
            $this->response[$element] = array(
                'name'=>$name
            );
        }
    }

    private function checkLabelDoesntStartWithId($label, $id) {
        $terminators = '. :';
    
        $paramId = (string)$id;
        if(substr($label, 0, strlen($paramId)) == $paramId &&
                (strlen($paramId) == strlen($label) || strpos($terminators, $label[strlen($paramId)]) != false)) {
            return true;
        }
        return false;
    }
    
    private function getChannelOptions($config) {
        if(isset($config) == false || $config == "") {
            return array();
        }
        return explode(',', trim($config));
    }
    
    private function getParameter($device, $parameterId) {
        if(!isset($device->parameters) || count($device->parameters) == 0) {
            return null;
        }
    
        foreach ($device->parameters as $parameter) {
            if($parameter['param_id'] == $parameterId) {
                return $parameter;
            }
        }
        
        return null;
    }
}

?>
