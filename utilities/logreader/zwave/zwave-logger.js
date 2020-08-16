function zwaveLoadLog(file) {
    // Initialise variables for loading
    data = [];
    nodes = {};
    countLines = 0;
    countEntries = 0;
    logState = "loading";
    logName = file.name;
    selectedNode = {};
    showOption = "LIST";

    var dbActions = [];

    logReader = new ZWaveLogReader();

    $q = Q;
    logReader.loadLogfile(file.files[0]).then(function () {
        data = logReader.getData();
        countLines = logReader.getLinesProcessed();
        nodes = logReader.getNodes();
        countEntries = data.length;

        // Clear the table
        var tble = '<table id="logList" style="width:100%;" class="table table-sm">';
        tble += '<thead style=""><tr>';
        tble += '<th>Time</th>';
        tble += '<th>Node</th>';
        tble += '<th>Entry</th>';
        tble += '</tr></thead><tbody style=""></tbody></table>';
        jQuery("#navTabs1_log").html(tble);

        // Clear the table
        tble = '<table id="logNodes" style="width:100%;" class="table table-sm">';
        tble += '<thead><tr>';
        tble += '<th>Node</th>';
        tble += '<th>Information</th>';
        tble += '</tr></thead><tbody></tbody></table>';
        jQuery("#navTabs1_nodes").html(tble);

        // Clear the table
        tble = '<table id="logFilter" style="width:100%;" class="table table-sm">';
        tble += '</tr></thead><tbody></tbody></table>';
        jQuery("#navTabs1_filter").html(tble);

        // Display all nodes in the filter to start with
        //               checkAllNodes();

        logState = "loaded";

        var cnt = 0;
        data.forEach(function (log) {
            cnt++;
            var newRow = "";
            var filter = "";

            filter += "log_filter_node" + log.node;

            //					if(log.errorFlag) {
            newRow += "<tr class='" + filter + "'>"; //warning'>";
            //					}
            //					else if(log.warnFlag) {
            //						newRow += "<tr class='error'>";
            //					}
            //					else {
            //						newRow += "<tr>";
            //					}

            newRow += "<td>";

            var res = log.time.split(".")
            newRow += res[0] + ".<small>" + res[1] + "</small></td>";
            newRow += "<td>";
            if (log.node != 255) {
                newRow += log.node;
            }
            newRow += "</td>";

            newRow += "<td><div>";

            newRow += "<a class='accordion-toggle' data-toggle='collapse' href='#info-" + cnt + "'>";

            newRow += log.content;

            // Special functions for some commands
            if (log.packet != null && log.packet.cmdClass != null) {
                switch (log.packet.cmdClass.funct) {
                    case 'MANUFACTURER_SPECIFIC_REPORT':
                        dbActions.push({
                            ref: dbActions.length + 1,
                            action: log.packet.cmdClass.funct,
                            node: log.packet.cmdClass.node
                        });
                        newRow += " <span id='dbresponse" + dbActions.length + "'></span>";
                        break;
                    case 'CONFIGURATION_GET':
                        dbActions.push({
                            ref: dbActions.length + 1,
                            action: log.packet.cmdClass.funct,
                            node: log.packet.cmdClass.node,
                            param_id: log.packet.cmdClass.param_id
                        });
                        newRow += " <span id='dbresponse" + dbActions.length + "'></span>";
                        break;
                    case 'CONFIGURATION_SET':
                        dbActions.push({
                            ref: dbActions.length + 1,
                            action: log.packet.cmdClass.funct,
                            node: log.packet.cmdClass.node,
                            param_id: log.packet.cmdClass.param_id
                        });
                        newRow += " <span id='dbresponse" + dbActions.length + "'></span>";
                        break;
                    case 'CONFIGURATION_REPORT':
                        dbActions.push({
                            ref: dbActions.length + 1,
                            action: log.packet.cmdClass.funct,
                            node: log.packet.cmdClass.node,
                            param_id: log.packet.cmdClass.param_id,
                            param_size: log.packet.cmdClass.param_size
                        });
                        newRow += " <span id='dbresponse" + dbActions.length + "'></span>";
                        break;
                }
            }


            if (log.expandedContent != null && log.expandedContent.length != 0) {
                newRow += " <span class='icon-menu-2'></span>";
            }

            newRow += "<span class='pull-right'>";
            //                newRow += (log.class == null ? "" : ("<span class='label label-info'>" + log.class + "</span>"));
            newRow += (log.txoptions == null ? "" : "&nbsp;" + log.txoptions);
            //					newRow += (log.stage == null ? "" : ("<span class='badge'>" + log.stage + "</span>"));
            newRow += (log.queue == null ? "" : "&nbsp;" + log.queue);

            newRow += "</a></div>";
            newRow += "<div class='collapse' id='info-" + cnt + "'>";
            if (log.packetData != null) {
                newRow += "<div><small>" + log.packetData + "</small></div>";
            }
            if (log.expandedContent != null) {
                newRow += "<div><small>" + log.expandedContent + "</small></div>";
            }
            newRow += "</div>";
            newRow += "</td>";
            newRow += "</tr>";

            jQuery("#logList  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        });

        // Create the nodes page list
        for (var ref in nodes) {
            var node = nodes[ref];
            if (typeof node !== "object") {
                continue;
            }

            newRow = "<td>Node " + node.id + "</td>";

            newRow += "<td><table class='table'>";

            newRow += "<tr><td>Alerts</td><td>";

            for (var i = 0; i < node.errors.length; i++) {
                //						newRow += "<div><span class='text-error fa fa-exclamation-circle'></span>&nbsp" + node.errors[i] + "</div>";
            }
            for (var i = 0; i < node.warnings.length; i++) {
                //						newRow += "<div><span class='text-warning fa fa-question-circle'></span>&nbsp" + node.warnings[i] + "</div>";
            }
            newRow += "</td></tr>";

            newRow += "<tr><td>Messages CANcelled</td><td>" + node.txErrorCan + "</td></tr>";
            newRow += "<tr><td>Messages NAKed</td><td>" + node.txErrorNak + "</td></tr>";
            newRow += "<tr><td>Neighbours</td><td>" + node.neighboursTotal + " total, ";
            newRow += node.neighboursListening + " listening, ";
            newRow += node.neighboursUnknown + " unknown</td></tr>";
            newRow += "<tr><td>Messages Sent</td><td>" + node.messagesSent + "</td></tr>";
            newRow += "<tr><td>Messages Complete</td><td>" + node.messagesComplete + "</td></tr>";
            newRow += "<tr><td>Messages Timed Out</td><td>" + node.responseTimeouts + " (" + node.retryPercent +
                "%)</td></tr>";
            newRow +=
                "<tr><td>Response Times</td><td>" + node.responseTimeMin + " / " + node.responseTimeAvg + " / " +
                node.responseTimeMax + "</td></tr>";

            newRow += "<tr><td>Messages Received</td><td>" + node.messagesRecv + "</td></tr>";
            newRow += "</table></td>";

            jQuery("#logNodes  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        };

        // Create the filter page list
        jQuery("#logFilter  > tbody:last-child").append("<tr><td><button type='button' onclick='jQuery(\".filtercheckbox\").prop(\"checked\", false);jQuery(\".filtercheckbox\").trigger(\"click\");'>Select All</button>&nbsp;<button type='button' onclick='jQuery(\".filtercheckbox\").prop(\"checked\", true);jQuery(\".filtercheckbox\").trigger(\"click\");'>Select None</button></td><td><button type='button' onclick='jQuery(\".highlightcheckbox\").prop(\"checked\", true);jQuery(\".highlightcheckbox\").trigger(\"click\");'>Select None</button></td></tr>");
        for (var ref in nodes) {
            var node = nodes[ref];
            if (typeof node !== "object") {
                continue;
            }

            newRow = "<td><input id='filterCheckboxNode" + node.id + "' class='filtercheckbox' type='checkbox' checked name='node" + node.id + "' onclick='jQuery(\".log_filter_node" + node.id + "\").css(\"display\", document.getElementById(\"filterCheckboxNode" + node.id + "\").checked?\"\":\"none\")'>&nbsp;&nbsp;Display Node " + node.id + "</td>";
            newRow += "<td><input id='highlightCheckboxNode" + node.id + "' class='highlightcheckbox' type='checkbox' name='node" + node.id + "' onclick='jQuery(\".log_filter_node" + node.id + "\").css(\"background-color\", document.getElementById(\"highlightCheckboxNode" + node.id + "\").checked?\"#CAFFD8\":\"#FFFFFF\")'>&nbsp;&nbsp;Highlight Node " + node.id + "</td>";
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        }

        for (var x = 0; x < dbActions.length; x++) {
            if (dbActions[x].action != "MANUFACTURER_SPECIFIC_REPORT") {
                continue;
            }
            if (nodes[dbActions[x].node] == null) {
                continue;
            }
            if (nodes[dbActions[x].node].manufacturer == null || nodes[dbActions[x].node].deviceID == null ||
                nodes[dbActions[x].node].deviceType == null) {
                continue;
            }

            jQuery.ajax({
                url: "http://www.cd-jackson.com/index.php?option=com_zwave_database&view=devicesummary&format=json&ref=" +
                    nodes[dbActions[x].node].manufacturer + "_" +
                    nodes[dbActions[x].node].deviceType + "_" + nodes[dbActions[x].node].deviceID,
                success: function (result) {
                    var device = jQuery.parseJSON(result);

                    if (device.parameters != null) {
                        device.parameters = [].concat(device.parameters);
                    }

                    for (var a = 0; a < dbActions.length; a++) {
                        if (nodes[dbActions[a].node] == null) {
                            continue;
                        }
                        if (parseInt(nodes[dbActions[a].node].manufacturer, 16) != device.manufacturer_ref) {
                            continue;
                        }
                        if (device.type_id.indexOf(nodes[dbActions[a].node].deviceType + ':' + nodes[dbActions[a].node].deviceID) == -1) {
                            continue;
                        }

                        switch (dbActions[a].action) {
                            case 'MANUFACTURER_SPECIFIC_REPORT':
                                if (device.id == null) {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-error'>Device UNKNOWN</span>");
                                }
                                else {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-info'>" + device.manufacturer + ", " + device.description + "</span> <span class='pull-right icon-new-tab' onclick='window.open(\"http://www.cd-jackson.com/index.php/zwave/zwave-device-database/zwave-device-list/devicesummary/" + device.id + "\")'></span>");
                                }
                                break;
                            case 'CONFIGURATION_GET':
                            case 'CONFIGURATION_SET':
                                if (device.parameters == null) {
                                    break;
                                }
                                var param = null;
                                for (var p = 0; p < device.parameters.length; p++) {
                                    if (device.parameters[p].param_id == dbActions[a].param_id) {
                                        param = device.parameters[p];
                                        break;
                                    }
                                }
                                if (param == null) {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-warning'>Parameter UNKNOWN</span>");
                                }
                                else {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-info'>" + param.label + "</span>");
                                }
                                break;
                            case 'CONFIGURATION_REPORT':
                                if (device.parameters == null) {
                                    break;
                                }
                                var param = null;
                                for (var p = 0; p < device.parameters.length; p++) {
                                    if (device.parameters[p].param_id == dbActions[a].param_id) {
                                        param = device.parameters[p];
                                        break;
                                    }
                                }
                                if (param == null) {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-warning'>Parameter UNKNOWN</span>");
                                }
                                else if (dbActions[a].param_size != param.size) {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-error'>Size MISMATCH</span> <span class='label label-info'>" + param.label + "</span>");
                                }
                                else {
                                    jQuery("#dbresponse" + (a + 1)).html("<span class='label label-info'>" + param.label + "</span>");
                                }
                                break;
                        }
                    }
                }
            }
            );
        }

        jQuery('#ZWaveLogTab a[href="#view-log"]').tab('show');
    }
    );
}
