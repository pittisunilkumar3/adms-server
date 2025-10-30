# Attendance System

A simplified attendance recording system designed to receive and store attendance data from biometric devices. This system is built using Laravel and focuses solely on attendance functionality.

## Features

- Receive attendance data from biometric devices (ZKTeco protocol)
- Store attendance records in database
- View attendance records through web interface
- Automatic device handshake handling

## How It Works

1. **Device Handshake**: Biometric devices connect to the server via `/iclock/cdata` endpoint
2. **Data Reception**: Attendance data is sent from devices to the server
3. **Data Storage**: Attendance records are stored in the `staff_attendance` table
4. **Web Interface**: View all attendance records at `/attendance`

## Installation

### Prerequisites

Before you begin, ensure you have the following installed on your system:

- PHP >= 8.0
- Composer
- MySQL or any other supported database
- Web server (Apache, Nginx, etc.)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/saifulcoder/adms-server-ZKTeco.git attendance-system
   cd attendance-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Copy the `.env` file**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Configure the `.env` file**
   Open the `.env` file and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=attendance_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run the migrations**
   ```bash
   php artisan migrate
   ```
   This will create the `staff_attendance` table.

7. **Serve the application**
   ```bash
   php artisan serve
   ```

8. **Access the application**
   - Web Interface: `http://localhost:8000/attendance`
   - Device Endpoint: `http://localhost:8000/iclock/cdata`

## Device Configuration

Configure your biometric device to connect to:
- **Server URL**: `http://your-server-ip:8000/iclock/cdata`
- **Protocol**: ZKTeco Push Protocol

## Database Structure

The system uses a single table `staff_attendance` with the following fields:
- `id`: Primary key
- `date`: Attendance date
- `staff_id`: Employee/Staff ID from biometric device
- `staff_attendance_type_id`: Type of attendance (default: 1)
- `biometric_attendence`: Boolean flag for biometric attendance
- `is_authorized_range`: Authorization status
- `biometric_device_data`: JSON data from device
- `remark`: Additional notes
- `is_active`: Active status
- `created_at`, `updated_at`: Timestamps


## Authors

- [@saifulcoder](https://github.com/saifulcoder)

## For Improvement and project

contact us saiful.coder@gmail.com

## Contributing

This project helps you and you want to help keep it going? Buy me a coffee:
<br> <a href="https://www.buymeacoffee.com/saifulcoder" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: 61px !important;width: 174px !important;box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;" ></a><br>
or via <br>
<a href="https://saweria.co/saifulcoder">https://saweria.co/saifulcoder</a>

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.