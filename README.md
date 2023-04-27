# NginxLogger
Displays useful information from nginx access.log server

Access Log Analyzer

This is a web application for analyzing access logs of a web server. It allows you to view statistics such as requests by IP address, requests by country, and requests by status code. You can also filter the access log entries by IP address, city, region, country, and status code, and sort them by various columns.

**How It Works**

The application consists of two parts: a Bash script that extracts log information and returns it in JSON format, and a Flask web server that serves the log data and provides a user interface for viewing and analyzing the data.

The Bash script, access.sh, reads log data from an Nginx access log file and uses awk to extract relevant information, such as client IP addresses, timestamps, HTTP methods, resources, and HTTP status codes. The script then formats this information into JSON and outputs it to the console.

The Flask web server, app.py, uses the subprocess module to run the access.sh script and retrieve the JSON-formatted log data. The server then provides an API endpoint at /log_info that returns the log data in JSON format.

The user interface is implemented using HTML, CSS, and JavaScript. The page makes an AJAX request to the /log_info API endpoint to retrieve the log data, and uses the Chart.js library to display the data in charts. The page also includes a table that displays the log data in a sortable and paginated format.


**Getting Started**

To use this application, you need to have a web server access log file in the Combined Log Format or a compatible format. You can configure the path to the log file in the scripts.

To run the application, you need to have a web server that supports PHP 7 or later. You can simply clone this repository to a directory of the server nginx is running on. Apache should have the same file format, I suppose.

**Key Feature**

**IP Location Lookup:** The application uses an IP geolocation API to obtain location information for each IP address in the access log. This information is displayed in the access log table and is used to generate charts showing requests by country and requests by IP address. 

**Catching**: To minimize the number of location lookups, I am catching the location data for each ip till expiry time is reached. This will help using free location lookup websites without hitting their monthly free benchmark.

**Sorting and Filtering:** The access log table can be sorted by clicking on the headers of each column. Additionally, the table can be filtered by IP address, country, city, and region using a search bar.
**Pagination:** The access log table is paginated to improve performance and make it easier to navigate. Users can choose how many rows to display per page, and can navigate between pages using a pagination bar.

**Usage**

Viewing statistics

The application shows you three charts with the following statistics:

Requests by IP address
Requests by country
Requests by status code
You can hover over the bars in each chart to see the exact number of requests.

Filtering access log entries
You can filter the access log entries by IP address, city, region, country, and status code by typing a search string in the corresponding input fields and pressing the "Filter" button. The table and charts will update to show only the entries that match the search string. You can clear the filters by clicking the "Clear filters" button.

Sorting access log entries
You can sort the access log entries by clicking on the table headers. Clicking the same header again will reverse the sort order. The table and charts will update to show the entries in the new order. Note that the sorting only applies to the current set of filtered entries.

Paginating access log entries
The table shows only a limited number of entries at a time. You can navigate between the pages using the pagination links at the bottom of the table.

Contributing

If you find a bug or have a feature request, please open an issue in the issue tracker. Pull requests are also welcome.
