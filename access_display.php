<?php
$flask_app_url = 'http://207.246.80.63:5000/log_info';
// Initialize a cURL session
$ch = curl_init();

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $flask_app_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the cURL session and get the response
$response = curl_exec($ch);

// Close the cURL session
curl_close($ch);
// Decode the JSON response
$data = json_decode($response, true)['log_info'];
//  IPInfo access token
$access_token = '9967415a86275a';

//Function to get the location of the fetched ip addresses
function get_location($ip, $access_token) {
    $cache_file = 'ip_cache.txt';
    $cache_ttl = 3600; // Cache Time To Live (TTL) in seconds (e.g., 1 hour)

    // Load cache data
    $cache_data = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];

    // Check if the IP is in the cache and if the cache entry is still valid
    if (isset($cache_data[$ip]) && (time() - $cache_data[$ip]['timestamp'] < $cache_ttl)) {
        return $cache_data[$ip]['location'];
    }

    // If the IP is not in the cache or the cache entry has expired, fetch the location data
    $ch = curl_init("https://ipinfo.io/{$ip}?token={$access_token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $ip_data = json_decode($response, true);
        if (isset($ip_data['city']) && isset($ip_data['region']) && isset($ip_data['country'])) {

            // Update the cache
            $cache_data[$ip] = [
                'location' => $ip_data,
                'timestamp' => time()
            ];
            file_put_contents($cache_file, json_encode($cache_data));

            return $ip_data;
        }
    }

    return [];
}
function calculate_requests_by_ip($data) {
    $requests_by_ip = [];
    foreach ($data as $entry) {
        $ip = $entry['client_ip'];
        if (isset($requests_by_ip[$ip])) {
            $requests_by_ip[$ip]++;
        } else {
            $requests_by_ip[$ip] = 1;
        }
    }
    arsort($requests_by_ip); // Sort by descending order
    return $requests_by_ip;
}

function calculate_requests_by_country($data, $access_token) {
    $requests_by_country = [];
    foreach ($data as $entry) {
        $location_data = get_location($entry['client_ip'], $access_token);
        $country = isset($location_data['country']) ? $location_data['country'] : 'Unknown';

        if (isset($requests_by_country[$country])) {
            $requests_by_country[$country]++;
        } else {
            $requests_by_country[$country] = 1;
        }
    }
    arsort($requests_by_country); // Sort by descending order
    return $requests_by_country;
}

function calculate_requests_by_status_code($data) {
    $requests_by_status_code = [];
    foreach ($data as $entry) {
        $status_code = $entry['status_code'];
        if (isset($requests_by_status_code[$status_code])) {
            $requests_by_status_code[$status_code]++;
        } else {
            $requests_by_status_code[$status_code] = 1;
        }
    }
    ksort($requests_by_status_code); // Sort by ascending order
    return $requests_by_status_code;
}
$requests_by_ip = calculate_requests_by_ip($data);
$requests_by_country = calculate_requests_by_country($data, $access_token);
$requests_by_status_code = calculate_requests_by_status_code($data);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Access Log Analysis</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.0-alpha1/css/bootstrap.min.css"
        integrity="sha384-KyZXEAg3QhqLMpG8r+Knujsl7/1L_dstPt3HV5HzF6Gvk/e3s4wzg+XYIZ7fIeJp" crossorigin="anonymous">
    <link rel="stylesheet" href="page.css">
</head>

<body>
    <div class="container">
        <h1>Access Log Analysis</h1>
        <div class="filter-container">
            <input type="text" id="ipFilter" name="ipFilter" class="form-control d-inline" style="width: auto;"
                placeholder="Filter by IP">
            <input type="text" id="cityFilter" name="cityFilter" class="form-control d-inline" style="width: auto;"
                placeholder="Filter by City">
            <input type="text" id="regionFilter" name="regionFilter" class="form-control d-inline" style="width: auto;"
                placeholder="Filter by Region">
            <input type="text" id="countryFilter" name="countryFilter" class="form-control d-inline"
                style="width: auto;" placeholder="Filter by Country">
            <button class="btn btn-primary" onclick="filterTable()">Apply Filters</button>
        </div>
        <div class="charts-container chart-wrapper">
            <div class="chart-container">
                <canvas id="ipChart" width="400" height="200"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="countryChart" width="400" height="200"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="statusCodeChart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="table-responsive">
            <table id="accessLogTable" class="table table-hover">
                <tr>
                    <th>Client IP</th>
                    <th data-sort="timestamp">Visit Time</th>
                    <th data-sort="city">City</th>
                    <th data-sort="region">Region</th>
                    <th data-sort="country">Country</th>
                    <th>Postal Code</th>
                    <th>Host Node</th>
                    <th>Organization</th>
                    <th>Method</th>
                    <th>Resource</th>
                    <th>Status Code</th>
                </tr>
                <?php foreach ($data as $entry): ?>
                <?php $location_data = get_location($entry['client_ip'], $access_token); ?>
                <tr>
                    <td><?php echo $entry['client_ip']; ?></td>
                    <td><?php echo $entry['timestamp']; ?></td>
                    <td><?php echo isset($location_data['city']) ? $location_data['city'] : 'Unknown'; ?></td>
                    <td><?php echo isset($location_data['region']) ? $location_data['region'] : 'Unknown'; ?></td>
                    <td><?php echo isset($location_data['country']) ? $location_data['country'] : 'Unknown'; ?></td>
                    <td><?php echo isset($location_data['postal']) ? $location_data['postal'] : 'Unknown'; ?></td>
                    <td><?php echo isset($location_data['hostname']) ? $location_data['hostname'] : 'Unknown'; ?></td>
                    <td><?php echo isset($location_data['org']) ? $location_data['org'] : 'Unknown'; ?></td>
                    <td><?php echo $entry['method']; ?></td>
                    <td><?php echo $entry['resource']; ?></td>
                    <td><?php echo $entry['status_code']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div id="pagination"></div>
        </div>
        <script>
        // Convert PHP arrays to JavaScript arrays
        const requestsByIP = new Map(Object.entries(<?php echo json_encode($requests_by_ip); ?>));
        const requestsByCountry = new Map(Object.entries(<?php echo json_encode($requests_by_country); ?>));
        const requestsByStatusCode = new Map(Object.entries(<?php echo json_encode($requests_by_status_code); ?>));
        //console.log("requestsByIP keys:", Array.from(requestsByIP.keys()));
        //console.log("requestsByIP values:", Array.from(requestsByIP.values()));

        // Create IP chart
        const ipChartCtx = document.getElementById('ipChart').getContext('2d');
        const ipChart = new Chart(ipChartCtx, {
            type: 'bar',
            data: {
                labels: Array.from(requestsByIP.keys()),
                datasets: [{
                    label: 'Requests by IP',
                    data: Array.from(requestsByIP.values()),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    minBarLength: 10
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Create country chart
        const countryChartCtx = document.getElementById('countryChart').getContext('2d');
        const countryChart = new Chart(countryChartCtx, {
            type: 'bar',
            data: {
                labels: Array.from(requestsByCountry.keys()),
                datasets: [{
                    label: 'Requests by Country',
                    data: Array.from(requestsByCountry.values()),
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    minBarLength: 10
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Create status code chart
        const statusCodeChartCtx = document.getElementById('statusCodeChart').getContext('2d');
        const statusCodeChart = new Chart(statusCodeChartCtx, {
            type: 'bar',
            data: {
                labels: Array.from(requestsByStatusCode.keys()),
                datasets: [{
                    label: 'Requests by Status Code',
                    data: Array.from(requestsByStatusCode.values()),
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1,
                    minBarLength: 10
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        let filteredData = null;

        function updateCharts(filteredData) {
            // Calculate the new aggregated data for the charts
            const requestsByIP = {};
            const requestsByCountry = {};
            const requestsByStatusCode = {};

            filteredData.forEach(entry => {
                requestsByIP[entry.client_ip] = (requestsByIP[entry.client_ip] || 0) + 1;
                requestsByCountry[entry.country] = (requestsByCountry[entry.country] || 0) + 1;
                requestsByStatusCode[entry.status_code] = (requestsByStatusCode[entry.status_code] || 0) + 1;
            });

            // Update the charts with the new aggregated data
            ipChart.data.labels = Object.keys(requestsByIP);
            ipChart.data.datasets[0].data = Object.values(requestsByIP);
            ipChart.update();

            countryChart.data.labels = Object.keys(requestsByCountry);
            countryChart.data.datasets[0].data = Object.values(requestsByCountry);
            countryChart.update();

            statusCodeChart.data.labels = Object.keys(requestsByStatusCode);
            statusCodeChart.data.datasets[0].data = Object.values(requestsByStatusCode);
            statusCodeChart.update();
        }

        function filterTable() {
            const countryFilter = document.getElementById("countryFilter").value;
            const cityFilter = document.getElementById("cityFilter").value;
            const regionFilter = document.getElementById("regionFilter").value;
            const ipFilter = document.getElementById("ipFilter").value;

            const table = document.getElementById("accessLogTable");
            const rows = table.getElementsByTagName("tr");

            const filteredData = [];

            for (let i = 1; i < rows.length; i++) {
                const ip = rows[i].getElementsByTagName("td")[0].textContent;
                const city = rows[i].getElementsByTagName("td")[2].textContent;
                const region = rows[i].getElementsByTagName("td")[3].textContent;
                const country = rows[i].getElementsByTagName("td")[4].textContent;

                const ipMatch = !ipFilter || ip.includes(ipFilter);
                const cityMatch = !cityFilter || city.includes(cityFilter);
                const regionMatch = !regionFilter || region.includes(regionFilter);
                const countryMatch = !countryFilter || country.includes(countryFilter);

                const match = ipMatch && cityMatch && regionMatch && countryMatch;
                rows[i].style.display = match ? "" : "none";

                if (match) {
                    // Push matched data to the filteredData array
                    const entryData = {
                        row: rows[i],
                        client_ip: ip,
                        timestamp: rows[i].getElementsByTagName("td")[1].textContent,
                        city: city,
                        region: region,
                        country: country,
                        postal: rows[i].getElementsByTagName("td")[5].textContent,
                        hostname: rows[i].getElementsByTagName("td")[6].textContent,
                        org: rows[i].getElementsByTagName("td")[7].textContent,
                        method: rows[i].getElementsByTagName("td")[8].textContent,
                        resource: rows[i].getElementsByTagName("td")[9].textContent,
                        status_code: rows[i].getElementsByTagName("td")[10].textContent
                    };
                    filteredData.push(entryData);
                }
            }

            // Update charts based on the filtered data
            updateCharts(filteredData);

            // Update the table with the filtered data and reset the pagination
            paginateTable(15, filteredData);
        }

        $(document).ready(function() {
            // Initialize the table with 20 rows per page
            paginateTable(15);

            // Initialize the autocomplete functionality
            const cities = [];
            const regions = [];
            const countries = [];

            const table = document.getElementById("accessLogTable");
            const rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                const city = rows[i].getElementsByTagName("td")[2].textContent;
                const region = rows[i].getElementsByTagName("td")[3].textContent;
                const country = rows[i].getElementsByTagName("td")[4].textContent;

                if (!cities.includes(city)) cities.push(city);
                if (!regions.includes(region)) regions.push(region);
                if (!countries.includes(country)) countries.push(country);
            }

            $("#cityFilter").autocomplete({
                source: cities
            });
            $("#regionFilter").autocomplete({
                source: regions
            });
            $("#countryFilter").autocomplete({
                source: countries
            });
        });

        function compare(a, b, key, order) {
            if (key === "timestamp") {
                // Parse the timestamps into Unix timestamps
                const dateA = new Date(a.timestamp.replace(/\s\+\d{4}/, "")).getTime();
                const dateB = new Date(b.timestamp.replace(/\s\+\d{4}/, "")).getTime();
                if (dateA < dateB) {
                    return order === 'asc' ? -1 : 1;
                }
                if (dateA > dateB) {
                    return order === 'asc' ? 1 : -1;
                }
                return 0;
            } else {
                if (a[key] < b[key]) {
                    return order === 'asc' ? -1 : 1;
                }
                if (a[key] > b[key]) {
                    return order === 'asc' ? 1 : -1;
                }
                return 0;
            }
        }

        //sort the table once user clicks the headers
        function sortTable(sortKey) {
            const table = document.getElementById("accessLogTable");
            const sortOrder = table.getAttribute("data-sort-order") === "asc" ? "desc" : "asc";
            table.setAttribute("data-sort-order", sortOrder);

            if (!filteredData) {
                const rows = Array.from(table.getElementsByTagName("tr")).slice(1);
                filteredData = rows.map(row => {
                    const cells = row.getElementsByTagName("td");
                    return {
                        row: row,
                        client_ip: cells[0].textContent,
                        timestamp: cells[1].textContent,
                        city: cells[2].textContent,
                        region: cells[3].textContent,
                        country: cells[4].textContent,
                        postal: cells[5].textContent,
                        hostname: cells[6].textContent,
                        org: cells[7].textContent,
                        method: cells[8].textContent,
                        resource: cells[9].textContent,
                        status_code: cells[10].textContent
                    };
                });
            }

            filteredData.sort((a, b) => compare(a, b, sortKey, sortOrder));

            filteredData.forEach(data => {
                table.appendChild(data.row);
            });

            paginateTable(15, filteredData);
        }


        //function paginates the table
        function paginateTable(rowsPerPage, filteredData = null) {
            const table = document.getElementById("accessLogTable");
            const rows = filteredData ? filteredData.map(data => data.row) : Array.from(table.getElementsByTagName(
                "tr")).slice(1);
            const totalPages = Math.ceil(rows.length / rowsPerPage);
            let currentPage = 1;

            function displayPage(page) {
                // Hide all rows
                rows.forEach(row => row.style.display = "none");

                // Display the rows for the current page
                const startIndex = (page - 1) * rowsPerPage;
                const endIndex = Math.min(rows.length, startIndex + rowsPerPage);
                for (let i = startIndex; i < endIndex; i++) {
                    rows[i].style.display = "";
                }

                // Update the pagination
                const pagination = document.getElementById("pagination");
                pagination.innerHTML = "";
                for (let i = 1; i <= totalPages; i++) {
                    const pageLink = document.createElement("a");
                    pageLink.href = "#";
                    pageLink.innerHTML = i;
                    pageLink.className = i === page ? "active" : "";
                    pageLink.addEventListener("click", (e) => {
                        e.preventDefault();
                        displayPage(i);
                    });
                    pagination.appendChild(pageLink);
                }
            }

            // Display the first page
            displayPage(currentPage);
        }

        // Add event listeners to the table headers
        $("#accessLogTable").on("click", "[data-sort]", function() {
            const sortKey = $(this).attr("data-sort");
            sortTable(sortKey);
            filterTable(); // Call filterTable instead of manually updating the table and pagination
        });
        </script>
</body>

</html>