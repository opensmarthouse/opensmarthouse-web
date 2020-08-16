function zigbeeCreateCheckbox(ref, name) {
    return "<td><input id='filterCheckboxNode" + ref + "' class='filtercheckbox' type='checkbox' checked name='node" + ref + "' onclick='jQuery(\".log_filter_node" + ref + "\").css(\"display\", document.getElementById(\"filterCheckboxNode" + ref + "\").checked?\"\":\"none\")'>&nbsp;&nbsp;" + name + "</td>";
}

function zigbeeLoadLog(file) {
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

    logReader = new ZigBeeLogReader();

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

        var filterAsh = false;
        var filterSpi = false;
        var filterEzsp = false;
        var filterXBee = false;
        var filterZstack = false;
        var filterTelegesis = false;

        var cnt = 0;
        data.forEach(function (log) {
            cnt++;
            var newRow = "";
            var filter = "";

            switch (log.filter) {
                case "ash":
                    filterAsh = true;
                    break;
                case "spi":
                    filterSpi = true;
                    break;
                case "xbee":
                    filterXBee = true;
                    break;
                case "zstack":
                    filterZstack = true;
                    break;
                case "ezsp":
                    filterEzsp = true;
                    break;
                case "telegesis":
                    filterTelegesis = true;
                    break;
            }

            filter += "log_filter_node" + log.filter + " ";

            newRow += "<tr class='" + filter + log.result + "'>";
            newRow += "<td>";

            var res = log.time.split(".")
            newRow += res[0] + "." + res[1] + "</td>";
            newRow += "<td>";
            if (log.node) {
                if (log.node > 0xfff8) {
                    newRow += "BDCST";
                } else {
                    newRow += log.node;
                }
            }
            newRow += "</td>";

            newRow += "<td><div>";

            newRow += "<a class='accordion-toggle' data-toggle='collapse' href='#info-" + cnt + "'>";

            newRow += log.content;

            if (log.expandedContent != null && log.expandedContent.length != 0) {
                newRow += " <span class='icon-menu-2'></span>";
            }

            newRow += "<span class='pull-right'>";
            //                newRow += (log.class == null ? "" : ("<span class='label label-info'>" + log.class + "</span>"));
            newRow += (log.txoptions == null ? "" : "&nbsp;" + log.txoptions);
            //					newRow += (log.stage == null ? "" : ("<span class='badge'>" + log.stage + "</span>"));

            newRow += "</a></div>";
            newRow += "<div class='collapse' id='info-" + cnt + "'>";
            if (log.packetData != null) {
                newRow += "<div>" + log.packetData + "</div>";
            }
            if (log.expandedContent != null) {
                newRow += "<div>" + log.expandedContent + "</div>";
            }
            newRow += "</div>";
            newRow += "</td>";
            newRow += "</tr>";

            jQuery("#logList  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        });

        // Create the filter page list
        jQuery("#logFilter  > tbody:last-child").append("<tr><td><button type='button' onclick='jQuery(\".filtercheckbox\").prop(\"checked\", false);jQuery(\".filtercheckbox\").trigger(\"click\");'>Select All</button>&nbsp;<button type='button' onclick='jQuery(\".filtercheckbox\").prop(\"checked\", true);jQuery(\".filtercheckbox\").trigger(\"click\");'>Select None</button></td></tr>");
        for (var ref in nodes) {
            var node = nodes[ref];
            if (typeof node !== "object") {
                continue;
            }

            var nodeId = parseInt(node.nwk);
            switch (nodeId) {
                case 0xFFFF:
                    name = "BROADCAST_ALL_DEVICES";
                    break;
                case 0xFFFD:
                    name = "BROADCAST_RX_ON";
                    break;
                case 0xFFFC:
                    name = "BROADCAST_ROUTERS_AND_COORD";
                    break;
                default:
                    var name = "<span style=\"display: inline-block;width: 120px;\">Node " + node.nwk + "</span><span style=\"display: inline-block;width: 100px;\">" + nodeId.toString(16).toUpperCase().padStart(4, "0") + "</span>";
                    if (node.ieeeAddress != null) {
                        name += "<span style=\"display: inline-block;width: 120px;\">" + node.ieeeAddress + "</span>";
                    }
                    break;
            }

            newRow = zigbeeCreateCheckbox(node.nwk, name);
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        }

        if (filterAsh) {
            newRow = zigbeeCreateCheckbox("ash", "ASH Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodeash").css("display", "none");
            jQuery("#filterCheckboxNodeash").prop("checked", false);
        }

        if (filterSpi) {
            newRow = zigbeeCreateCheckbox("spi", "SPI Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodespi").css("display", "none");
            jQuery("#filterCheckboxNodespi").prop("checked", false);
        }

        if (filterZstack) {
            newRow = zigbeeCreateCheckbox("zstack", "ZStack Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodezstack").css("display", "none");
            jQuery("#filterCheckboxNodezstack").prop("checked", false);
        }

        if (filterXBee) {
            newRow = zigbeeCreateCheckbox("xbee", "XBee Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodexbee").css("display", "none");
            jQuery("#filterCheckboxNodexbee").prop("checked", false);
        }

        if (filterEzsp) {
            newRow = zigbeeCreateCheckbox("ezsp", "EZSP Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodeezsp").css("display", "none");
            jQuery("#filterCheckboxNodeezsp").prop("checked", false);
        }

        if (filterTelegesis) {
            newRow = zigbeeCreateCheckbox("telegesis", "Telegesis Frames");
            jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
            jQuery(".log_filter_nodetelegesis").css("display", "none");
            jQuery("#filterCheckboxNodetelegesis").prop("checked", false);
        }

        newRow = zigbeeCreateCheckbox("transaction", "Transaction Progress");
        jQuery("#logFilter  > tbody:last-child").append("<tr>" + newRow + "</tr>");
        jQuery(".log_filter_nodetransaction").css("display", "none");
        jQuery("#filterCheckboxNodetransaction").prop("checked", false);

        jQuery('.nav-tabs a[href="#navTabs1_log"]').tab('show');
    });
}