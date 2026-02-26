<?php
// booking_form.php - basic booking form
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book your car - Car Service</title>
  <style>
    /* minimal styling */
    label { display:block; margin-top:8px; }
    input, select, textarea { width:100%; padding:8px; box-sizing:border-box; }
    .container { max-width:700px; margin:20px auto; }
  </style>
  
</head>
<body>
  <div class="container">
    <h1>Book your car for service or repair</h1>
    <form action="submit_booking.php" method="post" enctype="multipart/form-data">
      <label>Your name*:
        <input type="text" name="customer_name" required maxlength="150">
      </label>

      <label>Email*:
        <input type="email" name="customer_email" required maxlength="255">
      </label>

      <label>Phone*:
        <input type="text" name="customer_phone" required maxlength="50">
      </label>

      <label>Car Make:
        <input type="text" name="car_make" maxlength="100">
      </label>

      <label>Car Model:
        <input type="text" name="car_model" maxlength="100">
      </label>

      <label>Car Year:
        <input type="text" name="car_year" maxlength="10">
      </label>

      <label>License Plate:
        <input type="text" name="license_plate" maxlength="50">
      </label>

      <label>Type of service/repair*:
        <select name="service_type" required>
          <option value="">Select...</option>
          <option value="General Service">General Service</option>
          <option value="Oil Change">Oil Change</option>
          <option value="Brake Repair">Brake Repair</option>
          <option value="Diagnostic">Diagnostic</option>
          <option value="Other">Other</option>
        </select>
      </label>

      <label>Details / Notes:
        <textarea name="details" rows="5"></textarea>
      </label>

      <label>Attach photo (optional, jpg/png/gif/webp, max 5MB):
        <input type="file" name="image">
      </label>

      <button type="submit">Submit booking</button>
    </form>
  </div>
</body>
</html>