from flask import Flask, jsonify
from flask_cors import CORS
import json
import subprocess

app = Flask(__name__)
CORS(app)

@app.route('/log_info')
def log_info():
    # Run the shell script and store the output
    script_output = subprocess.check_output('./access.sh', shell=True).decode('utf-8')

    # Parse the JSON output
    log_info_data = json.loads(script_output)

    # Return JSON data
    return jsonify(log_info_data)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
