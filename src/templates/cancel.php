<?php defined('ABSPATH') || exit; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Page</title>
    <!-- Add your CSS styles here -->
    <style>
        .success_container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .successh1 {
            color: #333;
            margin-left: 25%;
        }
        .successp {
            color: #666;
            font-size: 16px;
        }
        .success-message {
            color: #4CAF50;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="success_container">
        <h1 class="successh1">Failed!</h1>
        <div class="success-message">
            <p class="successp">Your transaction was failed.</p>
            <p class="successp">Please try again later!</p>
        </div>
        <!-- You can include additional content or links here -->
    </div>
</body>
</html>
