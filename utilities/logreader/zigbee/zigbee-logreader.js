/**
 * ZSmartSystems ZigBee Framework log viewer
 * Licensed under GPL. Not for commercial use without agreement.
 * @author Chris Jackson
 */
function ZigBeeLogReader() {
    // Constant definitions
    var ERROR = "danger";
    var WARNING = "warning";
    var INFO = "info";
    var SUCCESS = "";
    var fileName = "";

    var lastTime;

    // Some globals used by the processor
    var logTime = 0;
    var lastNode = 0;
    var lastCmd = {};
    var lastPacketRx = null;
    var lastPacketTx = null;
    var txQueueLen = 0;

    var packetsSent = 0;
    var packetsRecv = 0;
    var timeStart = 0;

    var countLines = 0;
    var countEntries = 0;
    var loadProgress = 0;
    var nodeInfoProcessed = false;
    var data = [];

    var nodes = {};

    this.getData = function () {
        return data;
    };

    this.getLinesProcessed = function () {
        return countLines;
    };

    this.getFileName = function () {
        return fileName;
    };

    this.getNodes = function () {
        return nodes;
    };

    function updateNode(node) {
        if (nodes[node.nwk] == null) {
            nodes[node.nwk] = node;
        }
        if (node.ieeeAddress != null) {
            nodes[node.nwk].ieeeAddress = node.ieeeAddress;
        }
    }

    var xbeeList = {
        XBeeModemStatusEvent: {
            display: [{
                name: "status"
            }]
        },
        XBeeTransmitStatusResponse: {
            display: [{
                name: "deliveryStatus"
            }]
        }
    };

    var zstackList = {
        ZstackAfDataRequestSreq: {
            display: [{
                name: "dstAddr"
            }]
        },
        ZstackAfRegisterSreq: {
            display: [{
                name: "endPoint"
            }, {
                name: "appProfId",
                formatter: formatZigBeeProfile
            }]
        },
        ZstackAfRegisterSrsp: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        ZstackAppCnfBdbCommissioningNotificationAreq: {
            display: [{
                name: "status"
            }, {
                name: "commissioningMode"
            }]
        },
        ZstackAppCnfBdbSetActiveDefaultCentralizedKeySreq: {
            display: [{
                name: "centralizedLinkKeyMode"
            }]
        },
        ZstackSysPingSrsp: {
            display: [{
                name: "capabilities",
                formatter: formatZstackCapabilities
            }]
        },
        ZstackSysResetIndAreq: {
            display: [{
                name: "reason"
            }]
        },
        ZstackSysResetReqAcmd: {
            display: [{
                name: "type"
            }]
        },
        ZstackSysSetTxPowerSreq: {
            display: [{
                name: "txPower"
            }]
        },
        ZstackSysSetTxPowerSrsp: {
            display: [{
                name: "txPower"
            }]
        },
        ZstackUtilGetDeviceInfoSrsp: {
            display: [{
                name: "deviceState"
            }, {
                name: "deviceType"
            }, {
                name: "ieeeAddress"
            }, {
                name: "shortAddr"
            }]
        },
        ZstackZbWriteConfigurationSreq: {
            display: [{
                name: "configId"
            }]
        },
        ZstackZbWriteConfigurationSrsp: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        ZstackZdoMsgCbRegisterSreq: {
            display: [{
                name: "clusterId"
            }]
        },
        ZstackZdoMsgCbRegisterSrsp: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        ZstackZdoStateChangeIndAreq: {
            display: [{
                name: "state"
            }]
        }
    };

    var telegesisList = {
        TelegesisAckMessageEvent: {
            display: [{
                name: "messageId"
            }]
        },
        TelegesisDisallowTcJoinCommand: {
            display: [{
                name: "disallowJoin"
            }]
        },
        TelegesisNetworkJoinedEvent: {
            display: [{
                name: "channel"
            }, {
                name: "panId"
            }, {
                name: "epanId"
            }]
        },
        TelegesisSetNetworkKeyCommand: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        TelegesisSetRegisterCommand: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        TelegesisSetTrustCentreLinkKeyCommand: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        }
    };

    var ezspList = {
        EzspCalculateSmacsHandler: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspCalculateSmacsResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspChildJoinHandler: {
            display: [{
                name: "childId"
            }, {
                name: "childEui64"
            }, {
                name: "childType"
            }]
        },
        EzspClearTemporaryDataMaybeStoreLinkKeyRequest: {
            display: [{
                name: "storeLinkKey"
            }]
        },
        EzspClearTemporaryDataMaybeStoreLinkKeyResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspEnergyScanResultHandler: {
            display: [{
                name: "channel"
            }, {
                name: "maxRssiValue"
            }]
        },
        EzspGenerateCbkeKeysHandler: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGenerateCbkeKeysResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetCertificateResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetCertificate283k1Response: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetConfigurationValueRequest: {
            display: [{
                name: "configId"
            }]
        },
        EzspGetConfigurationValueResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "configId"
            }, {
                name: "value"
            }]
        },
        EzspGetCurrentSecurityStateResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "configId"
            }]
        },
        EzspGetKeyTableEntryRequest: {
            display: [{
                name: "index"
            }]
        },
        EzspGetKeyTableEntryResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetKeyRequest: {
            display: [{
                name: "keyType"
            }]
        },
        EzspGetKeyResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetLibraryStatusRequest: {
            display: [{
                name: "libraryId"
            }]
        },
        EzspGetLibraryStatusResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspGetNetworkParametersResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "nodeType"
            }]
        },
        EzspGetNodeIdResponse: {
            display: [{
                name: "nodeId"
            }]
        },
        EzspGetPolicyRequest: {
            display: [{
                name: "policyId"
            }]
        },
        EzspGetPolicyResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "decisionId"
            }]
        },
        EzspIncomingMessageHandler: {
            display: [{
                name: "type"
            }]
        },
        EzspJoinNetworkResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspMessageSentHandler: {
            display: [{
                name: "indexOrDestination"
            }, {
                name: "messageTag"
            }, {
                name: "sequence"
            }, {
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspNetworkInitResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspSetConcentratorResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspSetConfigurationValueRequest: {
            display: [{
                name: "configId"
            }, {
                name: "value"
            }]
        },
        EzspNetworkStateResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspSendBroadcastRequest: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "messageTag"
            }],
            statistics: statsEzspStatus
        },
        EzspSendBroadcastResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "sequence"
            }],
            statistics: statsEzspStatus
        },
        EzspSendUnicastRequest: {
            display: [{
                name: "indexOrDestination"
            }, {
                name: "messageTag"
            }]
        },
        EzspSendUnicastResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "sequence"
            }],
            statistics: statsEzspStatus
        },
        EzspSetConfigurationValueResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspSetInitialSecurityStateResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspSetPolicyRequest: {
            display: [{
                name: "policyId"
            }, {
                name: "decisionId"
            }]
        },
        EzspSetPolicyResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspStackStatusHandler: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspStackTokenChangedHandler: {
            display: [{
                name: "tokenAddress"
            }]
        },
        EzspStartScanRequest: {
            display: [{
                name: "scanType"
            }]
        },
        EzspStartScanResponse: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }]
        },
        EzspTrustCenterJoinHandler: {
            display: [{
                name: "status",
                formatter: formatEzspStatus
            }, {
                name: "newNodeId"
            }, {
                name: "newNodeEui64"
            }]
        },
        EzspVersionRequest: {
            display: [{
                name: "desiredProtocolVersion"
            }]
        },
        EzspVersionResponse: {
            display: [{
                name: "protocolVersion"
            }, {
                name: "stackType"
            }, {
                name: "stackVersion"
            }]
        }
    };

    var packetList = {
        ActiveEndpointsResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "activeEpList"
            }
            ]
        },
        BindRequest: {
            display: [{
                name: "bindCluster"
            },
            {
                name: "srcAddress"
            },
            {
                name: "srcEndpoint"
            },
            {
                name: "dstAddress"
            },
            {
                name: "dstEndpoint"
            }
            ]
        },
        BindResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },]
        },
        ConfigureReportingCommand: {
            display: [{
                name: "cluster",
                formatter: formatZclCluster
            }]
        },
        ConfigureReportingResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "cluster",
                formatter: formatZclCluster
            }
            ]
        },
        DeviceAnnounce: {
            display: [{
                name: "nwkAddrOfInterest"
            },
            {
                name: "ieeeAddr"
            },
            {
                name: "capability",
                formatter: formatMacCapability
            }
            ]
        },
        DiscoverAttributesCommand: {
            display: [{
                name: "cluster",
                formatter: formatZclCluster
            }, {
                name: "startAttributeIdentifier"
            }]
        },
        DiscoverAttributesResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "cluster",
                formatter: formatZclCluster
            },
            {
                name: "discoveryComplete"
            }
            ]
        },
        IeeeAddressRequest: {
            display: [{
                name: "nwkAddrOfInterest"
            }, {
                name: "requestType"
            },
            {
                name: "startIndex"
            }
            ]
        },
        IeeeAddressResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }, {
                name: "ieeeAddrRemoteDev"
            }, {
                name: "nwkAddrRemoteDev"
            }
            ]
        },
        ImageBlockCommand: {
            display: [{
                name: "fileOffset"
            }
            ]
        },
        ImageBlockResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }, {
                name: "fileOffset"
            }
            ]
        },
        InitiateKeyEstablishmentRequestCommand: {
            display: [{
                name: "keyEstablishmentSuite",
                formatter: formatCbkeType
            }, {
                name: "ephemeralDataGenerateTime"
            }, {
                name: "confirmKeyGenerateTime"
            }
            ]
        },
        InitiateKeyEstablishmentResponse: {
            display: [{
                name: "requestedKeyEstablishmentSuite",
                formatter: formatCbkeType
            }, {
                name: "ephemeralDataGenerateTime"
            }, {
                name: "confirmKeyGenerateTime"
            }
            ]
        },
        ManagementBindRequest: {
            display: [{
                name: "startIndex"
            }
            ]
        },
        ManagementBindResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "startIndex"
            },
            {
                name: "bindingTableEntries"
            }
            ]
        },
        ManagementLqiRequest: {
            display: [{
                name: "startIndex"
            }]
        },
        ManagementLqiResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "neighborTableEntries"
            },
            {
                name: "startIndex"
            }
            ]
        },
        ManagementPermitJoiningRequest: {
            display: [{
                name: "permitDuration"
            }]
        },
        ManagementPermitJoiningResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }]
        },
        MoveToHueAndSaturationCommand: {
            display: [{
                name: "hue"
            }, {
                name: "saturation"
            }, {
                name: "transitionTime"
            }]
        },
        MoveToLevelWithOnOffCommand: {
            display: [{
                name: "level"
            }, {
                name: "transitionTime"
            }]
        },
        NetworkAddressRequest: {
            display: [{
                name: "ieeeAddr"
            },
            {
                name: "startIndex"
            }
            ]
        },
        NetworkAddressResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }, {
                name: "ieeeAddrRemoteDev",
                record: "ieeeAddress"
            }, {
                name: "nwkAddrRemoteDev"
            }
            ]
        },
        ManagementRoutingRequest: {
            display: [{
                name: "startIndex"
            }]
        },
        ManagementRoutingResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "routingTableEntries"
            },
            {
                name: "startIndex"
            }
            ]
        },
        MatchDescriptorRequest: {
            display: [{
                name: "profileId",
                formatter: formatZigBeeProfile
            }, {
                name: "inClusterList",
                formatter: formatClusterList
            }, {
                name: "outClusterList",
                formatter: formatClusterList
            }
            ]
        },
        MatchDescriptorResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }, {
                name: "matchList"
            }
            ]
        },
        NodeDescriptorResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }
            ]
        },
        PowerDescriptorResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }
            ]
        },
        ReadAttributesCommand: {
            display: [{
                name: "cluster",
                formatter: formatZclCluster
            },
            {
                name: "identifiers"
            }
            ]
        },
        ReadAttributesResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            },
            {
                name: "cluster",
                formatter: formatZclCluster
            },
            {
                name: "identifiers"
            }
            ]
        },
        ReportAttributesCommand: {
            display: [{
                name: "cluster",
                formatter: formatZclCluster
            }]
        },
        SimpleDescriptorRequest: {
            display: [{
                name: "endpoint"
            }
            ]
        },
        SimpleDescriptorResponse: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }, {
                name: "simpleDescriptor.endpoint"
            }, {
                name: "simpleDescriptor.profileId",
                formatter: formatZigBeeProfile
            }, {
                name: "simpleDescriptor.deviceId",
                formatter: formatDeviceId
            }, {
                name: "simpleDescriptor.inputClusterList",
                formatter: formatClusterList
            }, {
                name: "simpleDescriptor.outputClusterList",
                formatter: formatClusterList
            }
            ]
        },
        TerminateKeyEstablishment: {
            display: [{
                name: "statusCode",
                formatter: formatCbkeStatusCode
            }
            ]
        },
        UpgradeEndCommand: {
            display: [{
                name: "status",
                formatter: formatZclStatus
            }]
        },
        ZoneStatusChangeNotificationCommand: {
            display: [{
                name: "zoneStatus",
                formatter: formatIasZoneStatus
            }]
        }
    };

    var deviceTypeList = {
        0x0000: {
            name: "ON_OFF_SWITCH"
        },
        0x0002: {
            name: "ON_OFF_OUTPUT"
        },
        0x0009: {
            name: "MAINS_POWER_OUTLET"
        },
        0x000A: {
            name: "DOOR_LOCK"
        },
        0x0100: {
            name: "ON_OFF_LIGHT"
        },
        0x0101: {
            name: "DIMMABLE_LIGHT"
        },
        0x0102: {
            name: "COLOR_DIMMABLE_LIGHT"
        },
        0x0103: {
            name: "ON_OFF_LIGHT_SWITCH"
        },
        0x0104: {
            name: "DIMMER_SWITCH"
        },
        0x0105: {
            name: "COLOR_DIMMER_SWITCH"
        },
        0x0106: {
            name: "LIGHT_SENSOR"
        },
        0x0107: {
            name: "OCCUPANCY_SENSOR"
        },
        0x0301: {
            name: "THERMOSTAT"
        },
        0x0302: {
            name: "TEMPERATURE_SENSOR"
        }
    };

    var clusterList = {
        0x0000: {
            name: "BASIC"
        },
        0x0001: {
            name: "POWER_CONFIGURATION"
        },
        0x0002: {
            name: "DEVICE_TEMPERATURE_CONFIGURATION"
        },
        0x0003: {
            name: "IDENTITY"
        },
        0x0004: {
            name: "GROUPS"
        },
        0x0005: {
            name: "SCENES"
        },
        0x0006: {
            name: "ON_OFF"
        },
        0x0007: {
            name: "ON_OFF_SWITCH_CONFIGURATION"
        },
        0x0008: {
            name: "LEVEL_CONTROL"
        },
        0x0009: {
            name: "ALARMS"
        },
        0x000A: {
            name: "TIME"
        },
        0x000F: {
            name: "BINARY_INPUT_BASIC"
        },
        0x0015: {
            name: "COMMISSIONING"
        },
        0x0019: {
            name: "OTA_UPGRADE"
        },
        0x0020: {
            name: "POLL_CONTROL"
        },
        0x0101: {
            name: "DOOR_LOCK"
        },
        0x0201: {
            name: "THERMOSTAT"
        },
        0x0202: {
            name: "FAN_CONTROL"
        },
        0x0203: {
            name: "DEHUMIDIFICATION_CONTROL"
        },
        0x0204: {
            name: "THERMOSTAT_UI_CONFIGURATION"
        },
        0x0300: {
            name: "COLOR_CONTROL"
        },
        0x0400: {
            name: "ILLUMINANCE_MEASUREMENT"
        },
        0x0402: {
            name: "TEMPERATURE_MEASUREMENT"
        },
        0x0403: {
            name: "PRESSURE_MEASUREMENT"
        },
        0x0404: {
            name: "FLOW_MEASUREMENT"
        },
        0x0405: {
            name: "HUMIDITY_MEASUREMENT"
        },
        0x0406: {
            name: "OCCUPANCY_SENSING"
        },
        0x0500: {
            name: "IAS_ZONE"
        },
        0x0501: {
            name: "IAS_ACE"
        },
        0x0700: {
            name: "PRICE"
        },
        0x0701: {
            name: "DRLC"
        },
        0x0702: {
            name: "METERING"
        },
        0x0703: {
            name: "MESSAGING"
        },
        0x0704: {
            name: "TUNNELING"
        },
        0x0705: {
            name: "PREPAYMENT"
        },
        0x0707: {
            name: "CALENDAR"
        },
        0x0709: {
            name: "EVENTS"
        },
        0x0800: {
            name: "KEY_ESTABLISHMENT"
        },
        0x0B04: {
            name: "ELECTRICAL_MEASUREMENT"
        },
        0x1000: {
            name: "TOUCHLINK"
        }
    };

    function splitPacket(packet) {
        var data = {};
        var start = 0;
        while (start < packet.length) {
            for (var pos = start; pos < packet.length; pos++) {
                if (packet[pos] != " ") {
                    start = pos;
                    break;
                }
            }

            var end = start;
            for (var pos = start; pos < packet.length; pos++) {
                if (packet[pos] == "=") {
                    end = pos;
                    break;
                }
            }

            var key = packet.substring(start, end);
            start = end + 1;

            var bracket = 0;
            end = packet.length;
            for (var pos = start; pos <= packet.length; pos++) {
                if (packet[pos] == "[") {
                    bracket++;
                }
                if (packet[pos] == "]") {
                    bracket--;
                }

                if ((packet[pos] == "," && bracket == 0) || pos == packet.length) {
                    end = pos;
                    break;
                }
            }

            data[key] = packet.substring(start, end);
            start = end + 1;
        }

        return data;
    }

    function displayPacket(command, packet, stats) {
        var display = "";

        var data = splitPacket(packet);
        for (var cnt = 0; cnt < command.display.length; cnt++) {
            var name;
            var value;
            if (command.display[cnt].name.indexOf(".") != -1) {
                var key = command.display[cnt].name.substring(0, command.display[cnt].name.indexOf("."));
                name = command.display[cnt].name.substring(command.display[cnt].name.indexOf(".") + 1);

                var internalPacket = data[key].substring(data[key].indexOf(" [") + 2, data[key].length - 1);

                dataInternal = splitPacket(internalPacket);
                value = dataInternal[name];
            } else {
                name = command.display[cnt].name;
                value = data[command.display[cnt].name];
            }

            if (value == null || value == "null") {
                continue;
            }

            if (command.display[cnt].formatter != null) {
                display += " " + command.display[cnt].formatter(name, value);
            } else {
                display += " <span class='badge badge-info'><b>" + name + "</b>=" + value + "</span>";
            }

            if (stats != null && command.display[cnt].record != null) {
                stats[command.display[cnt].record] = value;
            }
        }

        if (command.statistics != null) {
            command.statistics(command, data);
        }

        return display;
    }

    var telegesisRegisterList = {
        0x00: {
            name: "S00 [CHANNEL MASK]"
        },
        0x01: {
            name: "S01 [POWER LEVEL]"
        },
        0x02: {
            name: "S02 [PAN ID]"
        },
        0x03: {
            name: "S03 [EPAN ID]"
        },
        0x04: {
            name: "S04 [LOCAL EUI]"
        },
        0x05: {
            name: "S05 [LOCAL NWK]"
        },
        0x06: {
            name: "S06 [PARENT EUI]"
        },
        0x07: {
            name: "S07 [PARENT NWK]"
        },
        0x08: {
            name: "S08 [NWK KEY]"
        },
        0x09: {
            name: "S09 [LINK KEY]"
        },
        0x0A: {
            name: "S0A [MAIN FUNCTION]"
        },
        0x0B: {
            name: "S0B [USER NAME]"
        },
        0x0C: {
            name: "S0C [PASSWORD]"
        },
        0x0D: {
            name: "S0D [DEVICE INFO]"
        },
        0x0E: {
            name: "S0E [PROMPT ENABLE1]"
        },
        0x0F: {
            name: "S0F [PROMPT ENABLE2]"
        }
    };

    function formatTelegesisRegister(key, value) {
        var register = value;

        if (telegesisRegisterList[parseInt(value, 16)] != null) {
            register = telegesisRegisterList[parseInt(value, 16)].name;
        }
        return "<span class='badge badge-info'>register" + "=" + register + "</span> ";
    }


    var iasZones = {
        0x0001: "ALARM1",
        0x0002: "ALARM2",
        0x0004: "TAMPER",
        0x0008: "BATTERY",
        0x0010: "SUPERVISION",
        0x0020: "RESTORE",
        0x0040: "TROUBLE",
        0x0080: "ACMAINS",
        0x0100: "TEST",
        0x0200: "BATTERYDEFECT"
    };

    // Note duplication to avoid hex/dec issues
    var zigbeeProfiles = {
        104: "HOME AUTOMATION",
        109: "SMART ENERGY",
        0x0104: "HOME AUTOMATION",
        0x0109: "SMART ENERGY",
        0xC05E: "LIGHT LINK"
    }

    var cbkeTypes = {
        1: "163k1",
        2: "283k1"
    }

    var cbkeStatusCodes = {
        1: "UNKNOWN_ISSUER",
        2: "BAD_KEY_CONFIRM",
        3: "BAD_MESSAGE",
        4: "NO_RESOURCES",
        5: "UNSUPPORTED_SUITE",
        6: "INVALID_CERTIFICATE"
    }

    function formatClusterList(key, value) {
        var data = "<span class='badge badge-info'>" + key + "=[";
        var clusterIds = value.substr(1, value.length - 1).split(",");
        var first = true;
        for (clusterId of clusterIds) {
            if (!first) {
                data += ",";
            }
            first = false;
            var val = parseInt(clusterId);
            if (clusterList[val] != null) {
                data += clusterList[val].name;
            } else {
                data += clusterId;
            }
        }
        data += "]</span>";
        return data;
    }

    function formatDeviceId(key, value) {
        var data = "<span class='badge badge-info'>" + key + "=";
        var val = parseInt(value);
        if (deviceTypeList[val] != null) {
            data += deviceTypeList[val].name;
        } else {
            data += value;
        }

        data += "</span>";
        return data;
    }

    function formatCbkeType(key, value) {
        var profile;
        var profileId = parseInt(value);
        if (cbkeTypes[profileId] != null) {
            profile = cbkeTypes[profileId];
        } else {
            profile = profileId;
        }

        return "<span class='badge badge-info'>" + key + "=" + profile + "</span>";
    }

    function formatCbkeStatusCode(key, value) {
        var code;
        var statusCode = parseInt(value);
        if (cbkeStatusCodes[statusCode] != null) {
            code = cbkeStatusCodes[statusCode];
        } else {
            code = statusCode;
        }

        return "<span class='badge badge-info'>" + key + "=" + code + "</span>";
    }

    function formatZigBeeProfile(key, value) {
        var profile;
        var profileId = parseInt(value);
        if (zigbeeProfiles[profileId] != null) {
            profile = zigbeeProfiles[profileId];
        } else {
            profile = profileId;
        }

        return "<span class='badge badge-info'>" + key + "=" + profile + "</span>";
    }

    function formatZstackCapabilities(key, value) {
        var capabilities = new Array();
        var capabilitiesId = parseInt(value);
        if (capabilitiesId & 0x0001) {
            capabilities.push("MT_CAP_SYS");
        }
        if (capabilitiesId & 0x0002) {
            capabilities.push("MT_CAP_MAC");
        }
        if (capabilitiesId & 0x0004) {
            capabilities.push("MT_CAP_NWK");
        }
        if (capabilitiesId & 0x0008) {
            capabilities.push("MT_CAP_AF");
        }
        if (capabilitiesId & 0x0010) {
            capabilities.push("MT_CAP_ZDO");
        }
        if (capabilitiesId & 0x0020) {
            capabilities.push("MT_CAP_SAPI");
        }
        if (capabilitiesId & 0x0040) {
            capabilities.push("MT_CAP_UTIL");
        }
        if (capabilitiesId & 0x0080) {
            capabilities.push("MT_CAP_UTIL");
        }
        if (capabilitiesId & 0x0100) {
            capabilities.push("MT_CAP_APP");
        }
        if (capabilitiesId & 0x1000) {
            capabilities.push("MT_CAP_ZOAD");
        }

        return "<span class='badge badge-info'>capabilities=" + capabilities.toString() + "</span>";
    }

    function formatIasZoneStatus(key, value) {
        var display = "";
        for (var zone in iasZones) {
            if (value & zone) {
                display += "<span class='badge badge-info'>" + iasZones[zone] + "</span>";
            }
        }

        return display;
    }

    function formatMacCapability(key, value) {
        var display = "";
        var capability = parseInt(value);
        if (capability & 0x01) {
            display += "<span class='badge badge-info'>ALT_PAN</span> ";
        }
        if (capability & 0x02) {
            display += "<span class='badge badge-info'>FFD</span> ";
        } else {
            display += "<span class='badge badge-info'>RFD</span> ";
        }
        if (capability & 0x04) {
            display += "<span class='badge badge-info'>MAINS</span> ";
        }
        if (capability & 0x08) {
            display += "<span class='badge badge-info'>RX_ON</span> ";
        }
        if (capability & 0x40) {
            display += "<span class='badge badge-info'>SEC</span> ";
        }
        if (capability & 0x80) {
            display += "<span class='badge badge-info'>ADDRESS</span> ";
        }
        return display;
    }

    function formatZclStatus(key, value) {
        var format = "success";
        if (value != "SUCCESS") {
            format = "warning";
        }
        return "<span class='badge badge-" + format + "'>" + key + "=" + value + "</span> ";
    }

    function formatZclCluster(key, value) {
        var cluster = value;
        if (clusterList[parseInt(value, 16)] != null) {
            cluster = clusterList[parseInt(value, 16)].name;
        }
        return "<span class='badge badge-info'>" + key + "=" + cluster + "</span> ";
    }

    function formatEzspStatus(key, value) {
        var format;
        switch (value) {
            case "SUCCESS":
            case "EZSP_SUCCESS":
            case "EMBER_JOINED_NETWORK":
            case "EMBER_NETWORK_UP":
            case "EMBER_SUCCESS":
                format = "success";
                break;
            default:
                format = "warning";
                break;
        }
        return "<span class='badge badge-" + format + "'>" + key + "=" + value + "</span>";
    }

    function processPacket(nwkAddress, packet) {
        var tmp;
        var cmd = packet.substring(0, packet.indexOf(" "));

        var node = {
            nwk: nwkAddress
        };

        i = packet.indexOf("TID=");
        if (i != -1) {
            tmp = packet.substring(i + 4);
            tid = tmp.substring(0, tmp.indexOf(","));
            if (tid == "NULL") {
                tid = "--";
            }
        }

        var display = "";
        display += "<span class='badge badge-info'>" + tid + "</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";

        if (packetList[cmd] != null) {
            display += displayPacket(packetList[cmd], packet.substring(packet.indexOf(",") + 2, packet.length - 1), node);
        }
        updateNode(node);

        return display;
    }

    // Definition of strings to search for in the log
    var processList = [{
        string: "RX CMD: ",
        ref: "Info",
        processor: processRxCmd
    }, {
        string: "TX CMD: ",
        ref: "Info",
        processor: processTxCmd
    }, {
        string: "RX EZSP: ",
        ref: "EZSP",
        processor: processRxEzsp
    }, {
        string: "TX EZSP: ",
        ref: "EZSP",
        processor: processTxEzsp
    }, {
        string: "<-- RX ASH frame: ",
        ref: "ASH",
        processor: processAshFrame
    }, {
        string: "--> TX ASH frame: ",
        ref: "ASH",
        processor: processAshFrame
    }, {
        string: "<-- RX ASH error: ",
        ref: "ASH",
        processor: processAshError
    }, {
        string: "TX Telegesis: ",
        ref: "Telegesis",
        processor: processTxTelegesis
    }, {
        string: "RX Telegesis: ",
        ref: "Telegesis",
        processor: processRxTelegesis
    }, {
        string: "TX Telegesis Data: ",
        ref: "Telegesis",
        processor: processTxTelegesisData
    }, {
        string: "RX Telegesis Data: ",
        ref: "Telegesis",
        processor: processRxTelegesisData
    }, {
        string: "Command for channel",
        ref: "Info",
        processor: processChannelCommand
    }, {
        string: " Updating ZigBee channel state ",
        ref: "STATE",
        processor: processChannelUpdate
    }, {
        string: "No command type found",
        ref: "Info",
        processor: processUnknownCommand
    }, {
        string: "Transaction state updated:",
        ref: "Transaction",
        processor: processTransactionUpdate
    }, {
        string: "RX ZSTACK: ",
        ref: "ZSTACK",
        processor: processZstackFrame
    }, {
        string: "TX ZSTACK: ",
        ref: "ZSTACK",
        processor: processZstackFrame
    }, {
        string: "RX XBEE: ",
        ref: "XBEE",
        processor: processXBeeFrame
    }, {
        string: "TX XBEE: ",
        ref: "XBEE",
        processor: processXBeeFrame
    }, {
        string: "<-- RX SPI frame: ",
        ref: "SPI",
        processor: processSpiFrame
    }, {
        string: "--> TX SPI frame: ",
        ref: "SPI",
        processor: processSpiFrame
    }, {
        string: "state >>> ",
        ref: "STATE",
        processor: processConnectedRoomChannelUpdate
    }, {
        string: "statechanged >>> ",
        ref: "STATE",
        processor: processConnectedRoomChannelUpdate
    }];

    // Array of node information
    var nodes = {};

    function HEX2DEC(number) {
        // Return error if number is not hexadecimal or contains more than ten characters (10 digits)
        if (!/^[0-9A-Fa-f]{1,10}$/.test(number)) {
            return '#NUM!';
        }

        // Convert hexadecimal number to decimal
        var decimal = parseInt(number, 16);

        // Return decimal number
        return (decimal >= 549755813888) ? decimal - 1099511627776 : decimal;
    }

    function setStatus(cfg, status) {
        if (cfg.result == ERROR) {
            return;
        }
        if (status == ERROR) {
            cfg.result = ERROR;
            return;
        }

        if (cfg.result == WARNING) {
            return;
        }
        if (status == WARNING) {
            cfg.result = WARNING;
            return;
        }

        if (cfg.result == INFO) {
            return;
        }
        if (status == INFO) {
            cfg.result = INFO;
            return;
        }

        cfg.result = SUCCESS;
    }

    function statsEzspStatus(command, data) {
    }

    function processChannelCommand(processor, line) {
        var data = {
            result: SUCCESS
        };

        data.content = "<span class='badge badge-pill badge-danger'>COMMAND RECEIVED</span> <span class='badge badge-warning'>" + line.slice(line.indexOf("Command for channel ") + 20, line.indexOf(' -->')) + "</span> <span class='badge'>" + line.slice(line.indexOf("--> ") + 4) + "</span>";
        return data;
    }

    function processChannelUpdate(processor, line) {
        var data = {};
        data.content = "<span class='badge badge-pill badge-success'>STATE UPDATE</span> <span class='badge badge-warning'>" + line.slice(line.indexOf("Updating ZigBee channel state ") + 30, line.indexOf(' to ')) + "</span> <span class='badge'>" + line.slice(line.indexOf(" to ") + 4) + "</span>";
        return data;
    }

    function processConnectedRoomChannelUpdate(processor, line) {
        return null;
        var cmp = line.split("/");
        if (cmp.length != 4) {
            return null;
        }
        if (cmp[2].indexOf("zigbee") != 0) {
            return null;
        }
        var data = {};
        data.content = "<span class='badge badge-pill badge-success'>STATE UPDATE</span> <span class='badge'> " + cmp[2] + "</span> <span class='badge'>" + line.slice(line.indexOf("\"value\":") + 9, line.indexOf('\"}')) + "</span>";
        return data;
    }

    function processSpiFrame(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 18);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));
        var dir = line.substring(i + 4, i + 6);
        sendData.packetData = cmdStart;

        if (dir == "TX") {
            sendData.content = "<span class='badge badge-pill badge-danger'>SPI TX</span> ";
        } else {
            sendData.content = "<span class='badge badge-pill badge-success'>SPI RX</span> ";
        }

        var severity = "info";
        if (cmd == "ERROR") {
            severity = "danger";
        } else if (cmd == "ACK") {
            severity = "success";
        } else if (cmd == "NAK") {
            severity = "warning";
        }

        var cmdType = "unknown";
        switch (HEX2DEC(cmd)) {
            case 0x00:
                cmdType = "RESET";
                severity = "danger";
                break;
            case 0x01:
                cmdType = "TOO_LONG";
                severity = "danger";
                break;
            case 0x02:
                cmdType = "ABORT";
                severity = "danger";
                break;
            case 0x03:
                cmdType = "NO_TERMINATOR";
                severity = "danger";
                break;
            case 0x04:
                cmdType = "UNKNOWN_CMD";
                severity = "danger";
                break;
            case 0x0A:
                cmdType = "GET_VERSION";
                severity = "success";
                break;
            case 0x0B:
                cmdType = "GET_STATE";
                severity = "success";
                break;
            case 0xFD:
                cmdType = "BOOT";
                severity = "warning";
                break;
            case 0xFE:
                cmdType = "EZSP";
                severity = "success";
                break;
            default:
                if ((HEX2DEC(cmd) & 0xC0) == 0x80) {
                    cmdType = "VERSION";
                    severity = "warning";
                } else {
                    cmdType = "STATE";
                    severity = "warning";
                }
                break;
        }

        sendData.filter = "spi";
        sendData.content += " <span class='badge badge-pill badge-" + severity + "'>" + cmdType + "</span> <span class='badge'>" + cmdStart + "</span> ";

        return sendData;
    }

    function processAshError(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 29);

        sendData.filter = "ash";
        sendData.content = "<span class='badge badge-pill badge-danger'>ASH RX ERROR</span> ";
        sendData.content += "<span class='badge badge-pill badge-danger'>" + cmdStart + "</span> ";

        return sendData;
    }

    function processAshFrame(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 26);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" ")).toUpperCase();
        var dir = line.substring(i + 4, i + 6);

        var ackNum;
        i = line.indexOf("ackNum=");
        if (i != -1) {
            ackNum = parseInt(line.substring(i + 7));
        }

        var frmNum;
        i = line.indexOf("frmNum=");
        if (i != -1) {
            frmNum = parseInt(line.substring(i + 7));
        }

        var reTx = false;
        i = line.indexOf("reTx=true");
        if (i != -1) {
            reTx = true;
        }

        var errorCode;
        i = line.indexOf("errorCode=");
        if (i != -1) {
            errorCode = parseInt(line.substring(i + 10));
        }

        var data;
        i = line.indexOf("data=");
        if (i != -1) {
            data = line.substring(i + 5);
            data = data.substring(0, data.indexOf("]"));
        }

        if (dir == "TX") {
            sendData.content = "<span class='badge badge-pill badge-danger'>ASH TX</span> ";
        } else {
            sendData.content = "<span class='badge badge-pill badge-success'>ASH RX</span> ";
        }

        var severity = "info";
        if (cmd == "ERROR") {
            severity = "danger";
        } else if (cmd == "ACK") {
            severity = "success";
        } else if (cmd == "NAK") {
            severity = "warning";
        }

        sendData.content += " <span class='badge badge-pill badge-" + severity + "'>" + cmd + "</span> ";

        if (frmNum != null) {
            sendData.content += " <span class='badge badge-pill badge-info'>FRM=" + frmNum + "</span> ";
        }
        if (ackNum != null) {
            sendData.content += " <span class='badge badge-pill badge-info'>ACK=" + ackNum + "</span> ";
        }
        if (reTx == true) {
            sendData.content += " <span class='badge badge-pill badge-danger'>RETX</span> ";
        }
        if (errorCode != null) {
            sendData.content += " <span class='badge badge-danger'>ERROR=" + errorCode + "</span> ";
        }
        if (data != null) {
            sendData.content += " <span class='badge badge-info'>" + data + "</span> ";
        }

        sendData.filter = "ash";

        return sendData;
    }

    function processZstackFrame(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 11);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));
        var dir = line.substring(i, i + 2);
        sendData.packetData = cmdStart;

        if (dir == "TX") {
            sendData.content = "<span class='badge badge-pill badge-danger'>ZSTACK TX</span> ";
        } else {
            sendData.content = "<span class='badge badge-pill badge-success'>ZSTACK RX</span> ";
        }

        var severity = "info";
        if (cmd == "ERROR") {
            severity = "danger";
        } else if (cmd == "ACK") {
            severity = "success";
        } else if (cmd == "NAK") {
            severity = "warning";
        }

        sendData.filter = "zstack";
        sendData.content += " <span class='badge badge-pill badge-" + severity + "'>" + cmd + "</span> ";

        if (zstackList[cmd] != null) {
            sendData.content += displayPacket(zstackList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }

        return sendData;
    }

    function processXBeeFrame(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 9);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));
        var dir = line.substring(i, i + 2);
        sendData.packetData = cmdStart;

        if (dir == "TX") {
            sendData.content = "<span class='badge badge-pill badge-danger'>XBEE TX</span> ";
        } else {
            sendData.content = "<span class='badge badge-pill badge-success'>XBEE RX</span> ";
        }

        var severity = "info";
        if (cmd == "ERROR") {
            severity = "danger";
        } else if (cmd == "ACK") {
            severity = "success";
        } else if (cmd == "NAK") {
            severity = "warning";
        }

        sendData.filter = "xbee";
        sendData.content += " <span class='badge badge-pill badge-" + severity + "'>" + cmd + "</span> ";

        if (xbeeList[cmd] != null) {
            sendData.content += displayPacket(xbeeList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }

        return sendData;
    }

    function processTxEzsp(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 9);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        var addrStart = cmdStart.indexOf("indexOrDestination=");
        if (addrStart != -1) {
            var addr = cmdStart.substring(addrStart + 19, addrStart + 23);
            sendData.node = addr;
        }

        sendData.packetData = cmdStart;
        sendData.filter = "ezsp";
        sendData.content = "<span class='badge badge-pill badge-danger'>EZSP TX</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";
        if (ezspList[cmd] != null) {
            sendData.content += displayPacket(ezspList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }
        return sendData;
    }

    function processRxEzsp(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 9);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        var addrStart = cmdStart.indexOf("sender=");
        if (addrStart != -1) {
            var addr = cmdStart.substring(addrStart + 7, addrStart + 11);
            sendData.node = addr;
        }
        var addrStart = cmdStart.indexOf("indexOrDestination");
        if (addrStart != -1) {
            var addr = cmdStart.substring(addrStart + 19, addrStart + 23);
            sendData.node = addr;
        }

        sendData.packetData = cmdStart;
        sendData.filter = "ezsp";
        sendData.content = "<span class='badge badge-pill badge-success'>EZSP RX</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";
        if (ezspList[cmd] != null) {
            sendData.content += displayPacket(ezspList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }
        return sendData;
    }

    function processTxTelegesis(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 14);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        sendData.packetData = cmdStart;
        sendData.filter = "telegesis";
        sendData.content = "<span class='badge badge-pill badge-danger'>TELEGESIS TX</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";
        if (telegesisList[cmd] != null) {
            sendData.content += displayPacket(telegesisList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }

        return sendData;
    }

    function processRxTelegesis(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 14);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        sendData.packetData = cmdStart;
        sendData.filter = "telegesis";
        sendData.content = "<span class='badge badge-pill badge-success'>TELEGESIS RX</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";
        if (telegesisList[cmd] != null) {
            sendData.content += displayPacket(telegesisList[cmd], cmdStart.substring(cmdStart.indexOf(" [") + 2, cmdStart.length - 1));
        }

        return sendData;
    }

    function processRxTelegesisData(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmd = line.substring(i + 19);

        sendData.packetData = cmd;
        sendData.filter = "telegesis";
        sendData.content = "<span class='badge badge-pill badge-success'>TELEGESIS RXDATA</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";

        return sendData;
    }

    function processTxTelegesisData(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmd = line.substring(i + 19);

        sendData.packetData = cmd;
        sendData.filter = "telegesis";
        sendData.content = "<span class='badge badge-pill badge-danger'>TELEGESIS TXDATA</span> <span class='badge badge-pill badge-info'>" + cmd + "</span>";

        return sendData;
    }

    function processTransactionUpdate(processor, line) {
        return null;
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var splits = line.substring(i + 27).split(" ");

        //        sendData.node = node;
        //        sendData.filter = node;
        var badgeState = "success";
        if (splits[5] == "FAILED") {
            badgeState = "danger";
        }

        sendData.content = "<span class='badge badge-pill badge-warning'>TRANSACTION STATE UPDATE</span> ";
        sendData.content += "<span class='badge-pill badge-info'>messageTag=" + splits[1] + "</span> ";
        sendData.content += "<span class='badge badge-pill'>CAUSE=" + splits[3] + "</span> ";
        sendData.content += "<span class='badge badge-pill badge-" + badgeState + "'>STATE=" + splits[5] + "</span>";
        sendData.filter = "transaction";

        return sendData;
    }

    function processTxCmd(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 8);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        sendData.packetData = cmdStart;

        i = line.indexOf(" -> ");
        if (i != -1) {
            cmdStart = line.substring(i + 4);
            node = cmdStart.substring(0, cmdStart.indexOf("/"));
        }

        sendData.node = node;
        sendData.filter = node;
        sendData.content = "<span class='badge badge-pill badge-danger'>TX</span> ";
        sendData.content += processPacket(node, sendData.packetData);

        return sendData;
    }

    function processRxCmd(processor, line) {
        var sendData = {
            result: SUCCESS
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var cmdStart = line.substring(i + 8);
        var cmd = cmdStart.substring(0, cmdStart.indexOf(" "));

        sendData.packetData = cmdStart;

        var node;

        i = line.indexOf(" -> ");
        if (i != -1) {
            // Only display messages TO node 0 to prevent viewing local loopbacks
            cmdStart = line.substring(i + 4);
            node = cmdStart.substring(0, cmdStart.indexOf("/"));
            if (node == "0") {
                //	return null
            }

            i--;
            while (true) {
                if (line[i] == ' ' || line[i] == '[') {
                    i++;
                    break;
                }
                i--;
                if (i == 0) {
                    break;
                }
            }
            cmdStart = line.substring(i);
            node = cmdStart.substring(0, cmdStart.indexOf("/"));
        }

        sendData.node = node;
        sendData.filter = node;
        sendData.content = "<span class='badge badge-pill badge-success'>RX</span> ";
        sendData.content += processPacket(node, sendData.packetData);

        return sendData;
    }

    function processUnknownCommand(processor, line) {
        var sendData = {
            result: ERROR
        };

        var i = line.indexOf(processor.string);
        if (i == -1) {
            return null;
        }

        var start = line.substring(line.indexOf("cluster=") + 8);
        var cluster = start.substring(start, start.indexOf(","));
        start = line.substring(line.indexOf("command=") + 8);
        var command = start.substring(start, start.indexOf(","));
        start = line.substring(line.indexOf("direction=") + 10);
        var direction = start.substring(start);

        if (clusterList[cluster] != null) {
            cluster = clusterList[cluster].name;
        }

        sendData.packetData = line;
        //        sendData.filter = "telegesis";
        sendData.content = "<span class='badge badge-pill badge-danger'>UNKNOWN COMMAND</span> ";
        sendData.content += "<span class='badge badge-pill badge-info'>cluster=" + cluster + "</span> ";
        sendData.content += "<span class='badge badge-pill badge-info'>command=" + command + "</span> ";
        sendData.content += "<span class='badge badge-pill badge-info'>direction=" + direction + "</span> ";
        //        sendData.content = "<span class='badge badge-pill badge-danger'>UNKNOWN COMMAND</span> <span class='badge badge-pill badge-info'>" + cluster + ":" + command + "->" + direction+ "</span>";

        return sendData;
    }

    function isCharNumber(c) {
        return c >= '0' && c <= '9';
    }

    function logProcessLine(line) {
        if (line == null || line.length === 0) {
            return;
        }

        var timeString;
        if ((isCharNumber(line[0]) && (line.indexOf("-") == -1 || line.indexOf(":") < line.indexOf("-")))) {
            timeString = line;
        } else if (line.indexOf("T") != -1 && (line.indexOf("T") < line.indexOf(":"))) {
            timeString = line.substr(line.indexOf("T") + 1);
        } else if (line.indexOf("_") != -1 && (line.indexOf("_") < line.indexOf(":"))) {
            timeString = line.substr(line.indexOf("_") + 1);
        } else {
            var cnt = line.indexOf(" ");
            while (line[++cnt] == " ");
            timeString = line.substr(cnt);
        }
        var time = moment(timeString.substr(0, 12), "HH:mm:ss.SSS");

        var node = 0;

        logTime = time.valueOf();

        if (timeStart === 0) {
            timeStart = logTime;
        }


        var log = null;

        processList.forEach(function (process) {
            if (line.indexOf(process.string) != -1) {
                log = {};
                if (process.processor != null) {
                    log = process.processor(process, line);
                }
                if (log != null) {
                    log.ref = process.ref;

                    if (process.content !== undefined) {
                        log.content = process.content;
                    }
                    if (log.endClassPacket !== undefined && log.endClassPacket.expandedContent !== undefined) {
                        log.expandedContent = log.endClassPacket.expandedContent;
                    }
                    if (process.status !== undefined) {
                        setStatus(log, process.status);
                    }
                }
            }
        });

        if (log != null) {
            log.time = time.format("HH:mm:ss.SSS");
            log.start = time.valueOf();

            return log;
        }

        return null;
    }

    this.loadLogfile = function (file) {
        fileName = file.name;
        var deferred = $q.defer();

        lastPacketRx = null;
        nodeInfoProcessed = false;
        countLines = 0;
        countEntries = 0;
        txQueueLen = 0;
        packetsSent = 0;
        packetsRecv = 0;
        timeStart = 0;

        data = [];
        nodes = [];

        // Check for the various File API support.
        if (window.FileReader) {
            // FileReader are supported.
            getAsText(file);
        } else {
            alert('FileReader is not supported in this browser.');
        }

        return deferred.promise;

        function getAsText(fileToRead) {
            var reader = new FileReader();
            // Read file into memory as UTF-8
            reader.readAsText(fileToRead);
            // Handle errors load
            reader.onload = loadHandler;
            reader.onerror = errorHandler;
        }

        function loadHandler(event) {
            var csv = event.target.result;
            processData(csv);
        }

        function processData(csv) {
            var allTextLines = csv.split(/\r\n|\n/);
            for (var i = 0; i < allTextLines.length; i++) {
                countLines++;
                var d = logProcessLine(allTextLines[i]);
                if (d != null && d.ref != null) {
                    d.id = countEntries++;
                    data.push(d);
                }
            }

            deferred.resolve();
        }

        function errorHandler(evt) {
            if (evt.target.error.name == "NotReadableError") {
                alert("Cannot read file !");
            }
        }
    };
}