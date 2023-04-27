#!/bin/bash

# Path to log file
logfile="/var/log/nginx/access.log"

# Extract log information and return log data in JSON format
log_info=$(awk '
    function parse_timestamp(timestamp_str) {
        gsub(/[[\]]/, "", timestamp_str);
        split(timestamp_str, dt_parts, /[\/: ]/);
        dt_formatted = sprintf("%04d %02d %02d %02d %02d %02d", dt_parts[3], (index("JanFebMarAprMayJunJulAugSepOctNovDec", dt_parts[2])+2)/3, dt_parts[1], dt_parts[4], dt_parts[5], dt_parts[6]);
        return mktime(dt_formatted);
    }

    {
        if ($6 == "\"GET" || $6 == "\"POST") {
            client_ip = $1;
            timestamp = substr($4,2);
            method = substr($6, 2);
            resource = $7;
            status_code = $9;
            time = parse_timestamp(timestamp);
            gsub(/\\[^u"]/,"\\\\&",resource); # handle invalid escape sequences

            print "{\"client_ip\": \"" client_ip "\", \"timestamp\": \"" timestamp "\", \"method\": \"" method "\", \"resource\": \"" resource "\", \"status_code\": \"" status_code "\"}"
        } else {
            print "{\"client_ip\": \"" client_ip "\", \"timestamp\": \"" timestamp "\", \"method\": \"INVALID\", \"resource\": \"INVALID\", \"status_code\": \"INVALID\"}"
        }
    }' "$logfile" | paste -sd "," -)

log_info="[$log_info]"

# Output JSON
echo "{\"log_info\": $log_info}"
