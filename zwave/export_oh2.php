<?php

namespace lib\core;

include('html_purifier.php');

class zwaveexportoh2
{
    // all public function will be available as actions
    // parameters are always $options and $name
    // $options will contain the options for the action
    // $name is the name of the action
    public function export($item)
    {
        global $firstChannel;
        global $thingId;

        $customChannels = false;

        $product = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $product .= '<thing:thing-descriptions bindingId="zwave"' . PHP_EOL;
        $product .= '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . PHP_EOL;
        $product .= '  xmlns:thing="https://openhab.org/schemas/thing-description/v1.0.0"' . PHP_EOL;
        $product .= '  xsi:schemaLocation="https://openhab.org/schemas/thing-description/v1.0.0' . PHP_EOL;
        $product .= '                      https://openhab.org/schemas/thing-description/v1.0.0">' . PHP_EOL;

        $verMin = sprintf('%06.3f', $item->version_min);
        $verMin = str_replace(".", "_", $verMin);

        $thingId = $item->manufacturer->uuid . '_' . $item->uuid . '_' . $verMin;

        $product .= PHP_EOL;
        $product .= '  <thing-type id="' . $thingId . '" listed="false">' . PHP_EOL;
        $product .= '    <label>' . zwaveHtmlPurifier($item->label . ' ' . trim($item->description)) . '</label>' . PHP_EOL;
        $description = "";
        if (strlen($item->description))
        {
            $description .= zwaveHtmlPurifier($item->description);
        }
        if (strlen($item->overview))
        {
            $description .= '<br />' . PHP_EOL;
            $description .= '<h1>Overview<h1>' . PHP_EOL;
            $description .= '<p>' . trim($item->overview) . '</p>' . PHP_EOL;
        }
        if (strlen($item->inclusion))
        {
            $description .= '<br />' . PHP_EOL;
            $description .= '<h2>Inclusion Information<h2>' . PHP_EOL;
            $description .= '<p>' . trim($item->inclusion) . '</p>' . PHP_EOL;
        }
        if (strlen($item->exclusion))
        {
            $description .= '<br />' . PHP_EOL;
            $description .= '<h2>Exclusion Information<h2>' . PHP_EOL;
            $description .= '<p>' . trim($item->exclusion) . '</p>' . PHP_EOL;
        }
        if (strlen($item->wakeup))
        {
            $description .= '<br />' . PHP_EOL;
            $description .= '<h2>Wakeup Information<h2>' . PHP_EOL;
            $description .= '<p>' . trim($item->wakeup) . '</p>' . PHP_EOL;
        }
        $description = zwaveHtmlPurifier($description);
        if ($description != strip_tags($description))
        {
            // contains HTML
            $product .= '    <description><![CDATA[' . PHP_EOL;
            $product .= $description . PHP_EOL;
            $product .= '    ]]>';
            $product .= '</description>' . PHP_EOL;
        }
        else if (strlen($description) != 0)
        {
            $product .= '    <description>';
            $product .= $description;
            $product .= '</description>' . PHP_EOL;
        }

        if ($item->category && $item->category->category)
        {
            $product .= '    <category>' . $item->category->category . '</category>' . PHP_EOL;
        }

        $channelCnt = array();

        $firstChannel = true;
        $numEndpoints = count($item->endpoints);

        for ($cntE = 0;$cntE < $numEndpoints;$cntE++)
        {
            $product .= $this->zwaveProcessOh2Endpoint($item, $cntE);
        }

        if ($firstChannel == false)
        {
            $product .= '    </channels>' . PHP_EOL;
        }

        $versionminDisplay = floor($item->version_min) . '.' . round(($item->version_min - floor($item->version_min)) * 1000);
        $versionmaxDisplay = floor($item->version_max) . '.' . round(($item->version_max - floor($item->version_max)) * 1000);

        $product .= PHP_EOL;
        $product .= '    <!-- DEVICE PROPERTY DEFINITIONS -->' . PHP_EOL;
        $product .= '    <properties>' . PHP_EOL;
        $product .= '      <property name="vendor">' . zwaveHtmlPurifier($item->manufacturer->label) . '</property>' . PHP_EOL;
        $product .= '      <property name="modelId">' . zwaveHtmlPurifier($item->label) . '</property>' . PHP_EOL;
        $product .= '      <property name="manufacturerId">' . sprintf("%04X", $item->manufacturer->reference) . '</property>' . PHP_EOL;
        $product .= '      <property name="manufacturerRef">' . $item->device_ref . '</property>' . PHP_EOL;
        if ($item->version_min != 0.0)
        {
            $product .= '      <property name="versionMin">' . $versionminDisplay . '</property>' . PHP_EOL;
        }
        if ($item->version_max != 255.255)
        {
            $product .= '      <property name="versionMax">' . $versionmaxDisplay . '</property>' . PHP_EOL;
        }
        $product .= '      <property name="dbReference">' . $item->database_id . '</property>' . PHP_EOL;

        foreach ($item->endpoints as $endpoint)
        {
            foreach ($endpoint->commandclass as $commandclass)
            {
                $options = explode(',', trim($commandclass->config));
                if (isset($options) && count($options) > 0 && strlen($options[0]) != 0)
                {
                    $optionsProcessed = array();
                    foreach ($options as $option)
                    {
                        if ($option == 'ADD')
                        {
                            array_push($optionsProcessed, 'ccAdd');
                        }
                        else if ($option == 'REMOVE')
                        {
                            array_push($optionsProcessed, 'ccRemove');
                        }
                        else if ($option == 'NOGET')
                        {
                            array_push($optionsProcessed, 'getSupported=false');
                        }
                        else if ($option == 'FORCEVERSION')
                        {
                            $version = 1;
                            if ($commandclass->version > 0)
                            {
                                $version = $commandclass->version;
                            }
                            array_push($optionsProcessed, 'setVersion=' . $version);
                        }
                        else if ($option == 'NOGETSUPPORTED')
                        {
                            array_push($optionsProcessed, 'supportedGetSupported=false');
                        }
                        else
                        {
                            array_push($optionsProcessed, trim($option));
                        }
                    }

                    $product .= '      <property name="commandClass:' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $product .= ':' . $endpoint->number;
                    }
                    $product .= '">';
                    $product .= implode(",", $optionsProcessed);
                    $product .= '</property>' . PHP_EOL;
                }
            }
        }

        $defaultAssociations = "";
        $associations = $item->associations;
        $numAssociations = count($associations);
        for ($cnt = 0;$cnt < $numAssociations;$cnt++)
        {
            if ($associations[$cnt]->controller == 1)
            {
                if (strlen($defaultAssociations) != 0)
                {
                    $defaultAssociations .= ',';
                }
                $defaultAssociations .= $associations[$cnt]->group_id;
            }
        }

        if (strlen($defaultAssociations))
        {
            $product .= '      <property name="defaultAssociations">' . $defaultAssociations . '</property>' . PHP_EOL;
        }
        $product .= '    </properties>' . PHP_EOL;

        $product .= PHP_EOL;

        if (true)
        {
            $product .= '    <!-- CONFIGURATION DESCRIPTIONS -->' . PHP_EOL;
            $product .= '    <config-description>' . PHP_EOL;

            // Loop through and check for any write only parameters
            $cfgNormal = false;
            $cfgWriteOnly = false;
            $parameters = $item->parameters;
            $numParameters = count($parameters);
            for ($cntP = 0;$cntP < $numParameters;$cntP++)
            {
                $cfgNormal = true;
            }

            if ($numAssociations || $cfgNormal == true || $cfgWriteOnly == true)
            {
                $product .= PHP_EOL;
                $product .= '      <!-- GROUP DEFINITIONS -->';
            }

            if ($cfgNormal == true)
            {
                $product .= PHP_EOL;
                $product .= '      <parameter-group name="configuration">' . PHP_EOL;
                $product .= '        <context>setup</context>' . PHP_EOL;
                $product .= '        <label>Configuration Parameters</label>' . PHP_EOL;
                $product .= '      </parameter-group>' . PHP_EOL;
            }

            if ($numAssociations)
            {
                $product .= PHP_EOL;
                $product .= '      <parameter-group name="association">' . PHP_EOL;
                $product .= '        <context>link</context>' . PHP_EOL;
                $product .= '        <label>Association Groups</label>' . PHP_EOL;
                $product .= '      </parameter-group>' . PHP_EOL;
            }

            if ($cfgNormal == true)
            {
                $product .= PHP_EOL;
                $product .= '      <!-- PARAMETER DEFINITIONS -->';

                for ($cntP = 0;$cntP < $numParameters;$cntP++)
                {
                    $product .= $this->zwaveExportOh2Param($parameters[$cntP]);
                }
            }

            if ($numAssociations)
            {
                $product .= PHP_EOL;
                $product .= '      <!-- ASSOCIATION DEFINITIONS -->';
                for ($cnt = 0;$cnt < $numAssociations;$cnt++)
                {
                    $curAssociation = $associations[$cnt];
                    $product .= PHP_EOL;
                    $product .= '      <parameter name="group_' . $curAssociation->group_id . '" type="text" groupName="association"';
                    if ($curAssociation->max_nodes != 1)
                    {
                        $product .= ' multiple="true"';
                    }
                    $product .= '>' . PHP_EOL;
                    $product .= '        <label>' . $curAssociation->group_id . ': ' . zwaveHtmlPurifier($curAssociation->label) . '</label>' . PHP_EOL;

                    $description = "";
                    if (strlen($curAssociation->description))
                    {
                        $description = trim($curAssociation->description);
                    }
                    if (strlen($curAssociation->overview))
                    {
                        $description .= '<br />' . PHP_EOL;
                        $description .= '<h1>Overview<h1>' . PHP_EOL;
                        $description .= '<p>' . trim($curAssociation->overview) . '</p>' . PHP_EOL;
                    }

                    $description = zwaveHtmlPurifier($description);
                    if ($description != strip_tags($description))
                    {
                        $product .= '        <description><![CDATA[' . PHP_EOL;
                        $product .= $description . PHP_EOL;
                        $product .= '        ]]></description>' . PHP_EOL;
                    }
                    else if (strlen($description) != 0)
                    {
                        $product .= '        <description>';
                        $product .= $description;
                        $product .= '</description>' . PHP_EOL;
                    }

                    if ($curAssociation->max_nodes != 1)
                    {
                        $product .= '        <multipleLimit>' . $curAssociation->max_nodes . '</multipleLimit>' . PHP_EOL;
                    }

                    $product .= '      </parameter>' . PHP_EOL;
                }
            }

            $product .= PHP_EOL;
            $product .= '      <!-- STATIC DEFINITIONS -->';
            $product .= PHP_EOL;

            $product .= '      <parameter name="node_id" type="integer" min="1" max="232" readOnly="true" required="true">' . PHP_EOL;
            $product .= '        <label>Node ID</label>' . PHP_EOL;
            $product .= '        <advanced>true</advanced>' . PHP_EOL;
            $product .= '      </parameter>' . PHP_EOL;

            $product .= PHP_EOL;
            $product .= '    </config-description>' . PHP_EOL;
            $product .= PHP_EOL;
        }

        $product .= '  </thing-type>' . PHP_EOL . PHP_EOL;

        // Add any 'special' device specific channels
        $endpoints = $item->endpoints;
        $numEndpoints = count($endpoints);

        for ($cntE = 0;$cntE < $numEndpoints;$cntE++)
        {
            $currEndpoint = $endpoints[$cntE];
            $numClasses = count($currEndpoint->commandclass);
            for ($cntC = 0;$cntC < $numClasses;$cntC++)
            {
                $commandclass = $currEndpoint->commandclass[$cntC];
                $endpoint = $item->endpoints[$cntE];
                $channels = $commandclass->channels;
                $numChannels = count($channels);

                switch ($commandclass->commandclass_name)
                {
                    case 'COMMAND_CLASS_THERMOSTAT_FAN_STATE':
                        for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                        {
                            $currentChannel = $channels[$cntCh];
                            $product .= '  <channel-type id="' . $thingId . '_thermostat_fanstate">' . PHP_EOL;
                            $product .= '    <item-type>Number</item-type>' . PHP_EOL;
                            $product .= '    <label>Thermostat Fan State</label>' . PHP_EOL;
                            $product .= '    <description>Sets the thermostat fan state</description>' . PHP_EOL;
                            $product .= '    <category>Temperature</category>' . PHP_EOL;
                            $product .= '    <state pattern="%s">' . PHP_EOL;

                            $firstOption = true;
                            if (isset($currentChannel->options))
                            {
                                for ($cntO = 0;$cntO < count($currentChannel->options);$cntO++)
                                {
                                    if ($firstOption)
                                    {
                                        $product .= '      <options>' . PHP_EOL;
                                        $firstOption = false;
                                    }
                                    $product .= '        <option value="' . $currentChannel->options[$cntO]->value . '">' . $currentChannel->options[$cntO]->name . '</option>' . PHP_EOL;
                                }
                            }

                            if ($firstOption == false)
                            {
                                $product .= '      </options>' . PHP_EOL;
                            }
                            $product .= '    </state>' . PHP_EOL;
                            $product .= '  </channel-type>' . PHP_EOL;
                            $product .= PHP_EOL;
                        }
                        break;
                    case 'COMMAND_CLASS_THERMOSTAT_FAN_MODE':
                        for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                        {
                            $currentChannel = $channels[$cntCh];

                            $product .= '  <channel-type id="' . $thingId . '_thermostat_fanmode">' . PHP_EOL;
                            $product .= '    <item-type>Number</item-type>' . PHP_EOL;
                            $product .= '    <label>Thermostat Fan Mode</label>' . PHP_EOL;
                            $product .= '    <description>Sets the thermostat fan mode</description>' . PHP_EOL;
                            $product .= '    <category>Temperature</category>' . PHP_EOL;
                            $product .= '    <state pattern="%s">' . PHP_EOL;

                            $firstOption = true;
                            if (isset($currentChannel->options))
                            {
                                for ($cntO = 0;$cntO < count($currentChannel->options);$cntO++)
                                {
                                    if ($firstOption)
                                    {
                                        $product .= '      <options>' . PHP_EOL;
                                        $firstOption = false;
                                    }
                                    $product .= '        <option value="' . $currentChannel->options[$cntO]->value . '">' . $currentChannel->name . '</option>' . PHP_EOL;
                                }
                            }

                            if ($firstOption == false)
                            {
                                $product .= '      </options>' . PHP_EOL;
                            }
                            $product .= '    </state>' . PHP_EOL;
                            $product .= '  </channel-type>' . PHP_EOL;
                            $product .= PHP_EOL;
                        }
                        break;
                    case 'COMMAND_CLASS_THERMOSTAT_MODE':
                        for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                        {
                            $currentChannel = $channels[$cntCh];

                            $product .= '  <channel-type id="' . $thingId . '_thermostat_mode">' . PHP_EOL;
                            $product .= '    <item-type>Number</item-type>' . PHP_EOL;
                            $product .= '    <label>Thermostat Mode</label>' . PHP_EOL;
                            $product .= '    <description>Sets the thermostat mode</description>' . PHP_EOL;
                            $product .= '    <category>Temperature</category>' . PHP_EOL;
                            $product .= '    <state pattern="%s">' . PHP_EOL;

                            $firstOption = true;
                            if (isset($currentChannel->options))
                            {
                                for ($cntO = 0;$cntO < count($currentChannel->options);$cntO++)
                                {
                                    if ($firstOption)
                                    {
                                        $product .= '      <options>' . PHP_EOL;
                                        $firstOption = false;
                                    }
                                    $product .= '        <option value="' . $currentChannel->options[$cntO]->value . '">' . $currentChannel->options[$cntO]->name . '</option>' . PHP_EOL;
                                }
                            }

                            if ($firstOption == false)
                            {
                                $product .= '      </options>' . PHP_EOL;
                            }
                            $product .= '    </state>' . PHP_EOL;
                            $product .= '  </channel-type>' . PHP_EOL;
                            $product .= PHP_EOL;
                        }
                        break;
                    case 'COMMAND_CLASS_CONFIGURATION':
                        for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                        {
                            $currentChannel = $channels[$cntCh];

                            $configParam = null;
                            if ($currentChannel->config != null)
                            {
                                $options = explode(',', $currentChannel->config);
                                foreach ($options as $option)
                                {
                                    $sel = explode('=', $option);
                                    $configParamId = $sel[1];
                                    if ($sel[0] == 'parameter')
                                    {
                                        $parameters = $item->parameters;
                                        for ($cntP = 0;$cntP < count($parameters);$cntP++)
                                        {
                                            // Ignore write only parameters - we'll add them at the end
                                            if ($parameters[$cntP]->param_id == $configParamId)
                                            {
                                                $configParam = $parameters[$cntP];
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                            if ($configParam == null || !isset($configParam->options))
                            {
                                continue;
                            }

                            $product .= '  <channel-type id="' . $thingId . '_' . $currentChannel->name . '_param' . $configParamId . '">' . PHP_EOL;
                            $product .= '    <item-type>Number</item-type>' . PHP_EOL;
                            $product .= '    <label>' . zwaveHtmlPurifier($configParam->label) . '</label>' . PHP_EOL;
                            if (strlen($configParam->description))
                            {
                                $product .= '    <description>' . zwaveHtmlPurifier($configParam->description) . '</description>' . PHP_EOL;
                            }
                            $product .= '    <state pattern="%s">' . PHP_EOL;

                            if (count($configParam->options))
                            {
                                $product .= '      <options>' . PHP_EOL;
                                for ($cntO = 0;$cntO < count($configParam->options);$cntO++)
                                {
                                    $product .= '        <option value="' . $configParam->options[$cntO]->value . '">';
                                    $product .= $this->zwaveExportOh2Sanatise(zwaveHtmlPurifier($configParam->options[$cntO]->label)) . '</option>' . PHP_EOL;
                                }
                                $product .= '      </options>' . PHP_EOL;
                            }

                            $product .= '    </state>' . PHP_EOL;
                            $product .= '  </channel-type>' . PHP_EOL;
                            $product .= PHP_EOL;
                        }
                        break;
                }
            }
        }

        $product .= '</thing:thing-descriptions>' . PHP_EOL;

        return $product;
    }

    public function zwaveExportOh2Type($channel)
    {
        switch ($channel)
        {
            case "notification_send":
                return "DecimalType";
            case "sensor_door":
                return "OpenClosedType";
            case "basic_number":
                return "DecimalType";
            case "alarm_number":
                return "DecimalType";
            case "notification_access_control":
                return "DecimalType";
            case "notification_power_management":
                return "DecimalType";
            case "alarm_raw":
                return "StringType";
            case "switch_dimmer":
                return "PercentType";
            case "sensor_temperature":
            case "thermostat_setpoint":
                return "QuantityType";
        }
    }

    public function zwaveExportOh2Sanatise($name)
    {
        $transformations = array(
            "Disabled",
            "Enabled",
            "On",
            "Off",
            "Open",
            "Closed",
            "True",
            "False",
            "Locked",
            "Unlocked"
        );

        $lowerName = strtolower(trim($name));
        for ($x = 0;$x < count($transformations);$x++)
        {
            if (strtolower($transformations[$x]) == $lowerName)
            {
                return $transformations[$x];
            }
        }

        return $name;
    }

    public function zwaveExportOh2ChannelId($name)
    {
        $channelName = "";
        if (strpos($name, '[') == false)
        {
            $channelName = $name;
        }
        else
        {
            $channelName = substr($name, 0, strpos($name, '['));
        }
        return trim($channelName);
    }

    public function zwaveExportOh2ChannelType($name)
    {
        $channelType = "";
        if (strpos($name, '[') == false)
        {
            $channelType = $name;
        }
        else
        {
            $channelType = substr($name, strpos($name, '[') + 1, strpos($name, ']') - strpos($name, '[') - 1);
        }
        return trim($channelType);
    }

    public function zwaveExportOh2Param($parameter)
    {
        $product = PHP_EOL;
        $product .= '      <parameter name="config_' . $parameter->param_id . '_' . $parameter->size;
        if (isset($parameter->bitmask) && $parameter->bitmask != 0)
        {
            $product .= '_' . sprintf('%08X', $parameter->bitmask);
        }
        if (isset($parameter->write_only) && $parameter->write_only != 0)
        {
            $product .= '_wo';
        }
        $product .= '" type="integer" groupName="configuration"';

        if (isset($parameter->read_only) && $parameter->read_only != 0)
        {
            $product .= ' readOnly="true"';
        }

        // Don't worry about min and max if we are limiting to options (ie not free form entry)
        if ($parameter->limit_options || count($parameter->options) == 0 && (isset($parameter->write_only) && $parameter->write_only == 0))
        {
            $product .= PHP_EOL;

            if ($parameter->minimum == 0 && $parameter->maximum == 0)
            {
                switch ($parameter->size)
                {
                    case 1:
                        $max = 255;
                    break;
                    case 2:
                        $max = 65535;
                    break;
                    case 3:
                        $max = 16777215;
                    break;
                    case 4:
                    default:
                        $max = 4294967295;
                    break;
                }
                $product .= '                 min="0" max="' . $max . '"';
            }
            else
            {
                $product .= '                 min="' . $parameter->minimum . '" max="' . $parameter->maximum . '"';
            }
        }

        // unitType
        $product .= '>' . PHP_EOL;
        $product .= '        <label>' . $parameter->param_id . ': ' . zwaveHtmlPurifier($parameter->label) . '</label>' . PHP_EOL;

        $description = "";
        if (strlen($parameter->description))
        {
            $description = trim($parameter->description);
        }
        if (strlen($parameter->overview))
        {
            $description .= '<br />' . PHP_EOL;
            $description .= '<h1>Overview<h1>' . PHP_EOL;
            $description .= '<p>' . trim($parameter->overview) . '</p>' . PHP_EOL;
        }

        $description = zwaveHtmlPurifier($description);
        if ($description != strip_tags($description))
        {
            // contains HTML
            $product .= '        <description><![CDATA[' . PHP_EOL;
            $product .= $description . PHP_EOL;
            $product .= '        ]]>';
            $product .= '</description>' . PHP_EOL;
        }
        else if (strlen($description) != 0)
        {
            $product .= '        <description>' . $description . '</description>' . PHP_EOL;
        }

        $product .= '        <default>' . $parameter->default . '</default>' . PHP_EOL;

        if (count($parameter->options))
        {
            $product .= '        <options>' . PHP_EOL;
            for ($cntO = 0;$cntO < count($parameter->options);$cntO++)
            {
                $product .= '          <option value="' . $parameter->options[$cntO]->value . '">';
                $product .= $this->zwaveExportOh2Sanatise(zwaveHtmlPurifier($parameter->options[$cntO]->label)) . '</option>' . PHP_EOL;
            }
            $product .= '        </options>' . PHP_EOL;
        }
        if ($parameter->advanced)
        {
            $product .= '        <advanced>true</advanced>' . PHP_EOL;
        }
        if ($parameter->limit_options)
        {
            $product .= '        <limitToOptions>false</limitToOptions>' . PHP_EOL;
        }

        // unitLabel
        $product .= '      </parameter>' . PHP_EOL;

        return $product;
    }

    public function zwaveExportOh2ChannelProperty($endpoint, $cmdClass, $cmdType, $chanType, $basic, $config)
    {
        $product = '          <property name="binding:' . $cmdType . ':' . $chanType . '">' . $cmdClass;

        if ($endpoint != 0)
        {
            $product .= ':' . $endpoint;
        }
        if ($basic == 1)
        {

            $product .= ',COMMAND_CLASS_BASIC';
            if ($endpoint != 0)
            {
                $product .= ':' . $endpoint;
            }
        }
        if (strlen($config) != 0)
        {
            $product .= ";" . $config;
        }
        $product .= '</property>' . PHP_EOL;

        return $product;
    }

    public function zwaveExportOH2ChannelName($cmdclass, $endpoint)
    {
        $cmdClassSplit = substr($cmdclass, 14);

        $channelUID = $cmdClassSplit[2] . $cmdClassSplit[3] . $endpoint;
    }

    public function zwaveChannelLabel($channel)
    {
        $label = zwaveHtmlPurifier($channel->label);
        $label = $channel->label;
        if ($channel->name)
        {
            if ($channel->deprecated != "0000-00-00" && $channel->deprecated != "")
            {
                $label .= " [Deprecated]";
            }
        }

        return $label;
    }

    public function zwaveProcessOh2CommandClass($item, $cntE, $cntC)
    {
        global $firstChannel;
        global $thingId;

        $channelDefinition = "";
        $product = "";

        $endpoint = $item->endpoints[$cntE];
        $commandclass = $endpoint->commandclass[$cntC];
        $channels = $commandclass->channels;
        $numChannels = count($channels);

        // Don't add channels for command classes that are removed
        $options = explode(',', trim($commandclass->config));
        if (in_array("ccRemove", $options))
        {
            return $product;
        }

        $channelEndpoint = '';
        if ($endpoint->number != 0)
        {
            $channelEndpoint = $endpoint->number;
        }

        switch ($commandclass->commandclass_name)
        {
            case 'COMMAND_CLASS_BASIC':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="basic_' . $this->zwaveExportOh2ChannelId($currentChannel->name) . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:';

                    $dataType = $this->zwaveExportOh2Type($currentChannel->name);
                    if (isset($dataType) == false)
                    {
                        $dataType = "OnOffType";
                    }
                    $channelDefinition .= $dataType . '">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_BARRIER_OPERATOR':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $this->zwaveExportOh2ChannelId($currentChannel->name) . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:';

                    $channelDefinition .= 'DecimalType';

                    $channelDefinition .= '">' . $commandclass->commandclass_name;

                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_SWITCH_BINARY':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="switch_binary' . $channelEndpoint . '" typeId="switch_binary">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:OnOffType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_DOOR_LOCK':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $this->zwaveExportOh2ChannelId($currentChannel->name) . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($channels[$cntCh]) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;

                    switch ($currentChannel->name)
                    {
                        case "lock_door":
                            $channelDefinition .= '          <property name="binding:*:OnOffType">' . $commandclass->commandclass_name;
                        break;
                        case "sensor_door":
                            $channelDefinition .= '          <property name="binding:*:OpenClosedType">' . $commandclass->commandclass_name;
                        break;
                    }

                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_SWITCH_MULTILEVEL':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $channels[$cntCh]->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;

                    switch ($channels[$cntCh]->name)
                    {
                        case "blinds_control":
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "*", "PercentType", $commandclass->basic, "");
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "Command", "StopMoveType", false, "");
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "Command", "UpDownType", false, "");
                        break;
                        case "switch_startstop":
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "*", "StringType", 0, "");
                        break;
                        case "switch_dimmer":
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "*", "PercentType", $commandclass->basic, "");
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_MULTILEVEL", "Command", "OnOffType", false, "");;
                        break;
                    }

                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_INDICATOR':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;

                    switch ($currentChannel->name)
                    {
                        case "indicator_level":
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "*", "PercentType", $commandclass->basic, $channels[$cntCh]->config);
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "Command", "OnOffType", false, $channels[$cntCh]->config);
                        break;
                        default:
                            $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, $commandclass->commandclass_name, "*", "DecimalType", $commandclass->basic, $channels[$cntCh]->config);
                        break;
                    }
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_SWITCH_COLOR':
                $endpointNumber = '';
                if ($endpoint->number != 0)
                {
                    $endpointNumber = ':' . $endpoint->number;
                }
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $channels[$cntCh]->name . $channelEndpoint . '" typeId="' . $channels[$cntCh]->name . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    if ($channels[$cntCh]->name == 'color_color')
                    {
                        $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_COLOR", "*", "HSBType", false, $currentChannel->config);
                        $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_MULTILEVEL", "*", "PercentType", false, "");
                        $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_MULTILEVEL", "Command", "OnOffType", false, "");
                    }
                    else if ($currentChannel->name == 'color_temperature')
                    {
                        $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_COLOR", "*", "PercentType", false, $currentChannel->config);
                        $channelDefinition .= $this->zwaveExportOh2ChannelProperty($endpoint->number, "COMMAND_CLASS_SWITCH_MULTILEVEL", "Command", "OnOffType", false, "");
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_METER':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    if ($channels[$cntCh]->name == "meter_reset")
                    {
                        $channelDefinition .= '          <property name="binding:*:OnOffType">' . $commandclass->commandclass_name;
                    }
                    else
                    {
                        $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    }
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($channels[$cntCh]->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_METER_TBL_MONITOR':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
					$channelDefinition .= '          <property name="binding:*:DecimalType">COMMAND_CLASS_METER_TBL_MONITOR';
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($channels[$cntCh]->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_ALARM':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $dataType = $this->zwaveExportOh2Type($currentChannel->name);
                    if (isset($dataType) == false)
                    {
                        $dataType = "OnOffType";
                    }
                    $channelDefinition .= '      <channel id="' . $channels[$cntCh]->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:' . $dataType . '">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_SENSOR_ALARM':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:OnOffType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_SENSOR_BINARY':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    switch ($currentChannel->name)
                    {
                        case 'sensor_door':
                            $channelDefinition .= '          <property name="binding:*:OpenClosedType">';
                        break;
                        default:
                            $channelDefinition .= '          <property name="binding:*:OnOffType">';
                        break;
                    }
                    $channelDefinition .= $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;

                }
                break;
            case 'COMMAND_CLASS_SENSOR_MULTILEVEL':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $dataType = $this->zwaveExportOh2Type($currentChannel->name);
                    if (isset($dataType) == false)
                    {
                        $dataType = "DecimalType";
                    }

                    $channelDefinition .= '      <channel id="' . $this->zwaveExportOh2ChannelId($currentChannel->name) . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:' . $dataType . '">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_CENTRAL_SCENE':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_SCENE_ACTIVATION':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_CONFIGURATION':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $configParam = null;
                    $configParamId = 0;
                    if ($currentChannel->config != null)
                    {
                        $options = explode(',', $currentChannel->config);
                        foreach ($options as $option)
                        {
                            $sel = explode('=', $option);
                            $configParamId = $sel[1];
                            if ($sel[0] == 'parameter')
                            {
                                $configParam = $sel[1];

                                $parameters = $item->parameters;
                                for ($cntP = 0;$cntP < count($parameters);$cntP++)
                                {
                                    // Ignore write only parameters - we'll add them at the end
                                    if ($parameters[$cntP]->param_id == $configParamId)
                                    {
                                        $configParam = $parameters[$cntP];
                                    }
                                }
                                break;
                            }
                        }
                    }

                    $options = $configParam->options;

                    if ($options != null)
                    {
                        $channelDefinition .= '      <channel id="' . $currentChannel->name . '_param' . $configParamId . '" typeId="' . $thingId . '_' . $currentChannel->name . '_param' . $configParamId . '">' . PHP_EOL;
                    }
                    else
                    {
                        $channelDefinition .= '      <channel id="' . $currentChannel->name . '_param' . $configParamId . '" typeId="' . $currentChannel->name . '">' . PHP_EOL;
                    }
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_THERMOSTAT_OPERATING_STATE':
                $channelDefinition .= '      <channel id="thermostat_state' . $channelEndpoint . '" typeId="thermostat_state">' . PHP_EOL;
                if ($endpoint->number > 0)
                {
                    $channelDefinition .= '        <label>Thermostat Operating State ' . $endpoint->number . '</label>' . PHP_EOL;
                }
                else
                {
                    $channelDefinition .= '        <label>Thermostat Operating State</label>' . PHP_EOL;
                }
                $channelDefinition .= '        <properties>' . PHP_EOL;
                $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                if ($endpoint->number != 0)
                {
                    $channelDefinition .= ':' . $endpoint->number;
                }
                if ($commandclass->basic == 1)
                {
                    $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                }
                $channelDefinition .= '</property>' . PHP_EOL;
                $channelDefinition .= '        </properties>' . PHP_EOL;
                $channelDefinition .= '      </channel>' . PHP_EOL;
                break;
            case 'COMMAND_CLASS_THERMOSTAT_SETPOINT':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelName = $channelEndpoint;
                    if ($currentChannel->config != null)
                    {
                        $options = explode(',', $currentChannel->config);
                        foreach ($options as $option)
                        {
                            $sel = explode('=', $option);
                            if ($sel[0] == 'type')
                            {
                                $channelName = '_' . strtolower($sel[1]) . $channelEndpoint;
                            }
                        }
                    }

                    $dataType = $this->zwaveExportOh2Type($currentChannel->name);
                    if (isset($dataType) == false)
                    {
                        $dataType = "DecimalType";
                    }

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelName . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . zwaveHtmlPurifier($currentChannel->label) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:' . $dataType . '">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                break;
            case 'COMMAND_CLASS_THERMOSTAT_MODE':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="thermostat_mode' . $channelEndpoint . '" typeId="' . $thingId . '_thermostat_mode">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                $customChannels = true;
                break;
            case 'COMMAND_CLASS_THERMOSTAT_FAN_MODE':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="thermostat_fanmode' . $channelEndpoint . '" typeId="' . $thingId . '_thermostat_fanmode">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                $customChannels = true;
                break;
            case 'COMMAND_CLASS_THERMOSTAT_FAN_STATE':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="thermostat_fanstate' . $channelEndpoint . '" typeId="' . $thingId . '_thermostat_fanstate">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
                $customChannels = true;
                break;
            case 'COMMAND_CLASS_CLOCK':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_TIME_PARAMETERS':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_PROTECTION':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:DecimalType">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
            case 'COMMAND_CLASS_BATTERY':
                $channelDefinition .= '      <channel id="battery-level' . $channelEndpoint . '" typeId="system.battery-level">' . PHP_EOL;

                if ($channelEndpoint != "")
                {
                    $channelDefinition .= '        <label>Battery Level ' . $channelEndpoint . '</label>' . PHP_EOL;
                }

                $channelDefinition .= '        <properties>' . PHP_EOL;
                $channelDefinition .= '          <property name="binding:*:PercentType">' . $commandclass->commandclass_name . '</property>' . PHP_EOL;
                $channelDefinition .= '        </properties>' . PHP_EOL;
                $channelDefinition .= '      </channel>' . PHP_EOL;
            break;
            case 'COMMAND_CLASS_MANUFACTURER_PROPRIETARY':
                for ($cntCh = 0;$cntCh < $numChannels;$cntCh++)
                {
                    $currentChannel = $channels[$cntCh];

                    $dataType = $this->zwaveExportOh2Type($currentChannel->name);
                    if (isset($dataType) == false)
                    {
                        $dataType = "PercentType";
                    }
                    $channelDefinition .= '      <channel id="' . $currentChannel->name . $channelEndpoint . '" typeId="' . $this->zwaveExportOh2ChannelType($currentChannel->name) . '">' . PHP_EOL;
                    $channelDefinition .= '        <label>' . $this->zwaveChannelLabel($currentChannel) . '</label>' . PHP_EOL;
                    $channelDefinition .= '        <properties>' . PHP_EOL;
                    $channelDefinition .= '          <property name="binding:*:' . $dataType . '">' . $commandclass->commandclass_name;
                    if ($endpoint->number != 0)
                    {
                        $channelDefinition .= ':' . $endpoint->number;
                    }
                    if ($commandclass->basic == 1)
                    {
                        $channelDefinition .= ',' . 'COMMAND_CLASS_BASIC';
                        if ($endpoint->number != 0)
                        {
                            $channelDefinition .= ':' . $endpoint->number;
                        }
                    }
                    if ($currentChannel->config != null)
                    {
                        $channelDefinition .= ';' . $currentChannel->config;
                    }
                    $channelDefinition .= '</property>' . PHP_EOL;
                    $channelDefinition .= '        </properties>' . PHP_EOL;
                    $channelDefinition .= '      </channel>' . PHP_EOL;
                }
            break;
        }

        if (strlen($channelDefinition))
        {
            if ($firstChannel)
            {
                $firstChannel = false;
                $product .= PHP_EOL;
                $product .= '    <!-- CHANNEL DEFINITIONS -->' . PHP_EOL;
                $product .= '    <channels>' . PHP_EOL;
            }
            $product .= $channelDefinition;
        }

        return $product;
    }

    private function zwaveProcessOh2Endpoint($item, $cntE)
    {
        $product = "";

        $numClasses = count($item->endpoints[$cntE]->commandclass);
        for ($cntC = 0;$cntC < $numClasses;$cntC++)
        {
            $product .= $this->zwaveProcessOh2CommandClass($item, $cntE, $cntC);
        }

        return $product;
    }
}

?>
