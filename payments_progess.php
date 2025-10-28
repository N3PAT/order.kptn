<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <title>กำลังโหลด...</title>
  <style>
    body {
      background-color: white;
      font-family: 'Share Tech Mono', monospace;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .progress-container {
      width: 300px;
      border: 2px solid black;
      margin: 10px 0;
      background-color: white;
      overflow: hidden;
      display: flex;
      justify-content: flex-start;
    }

    .progress-bar {
      display: flex;
      gap: 2px;
    }

    .block {
      width: 20px;
      height: 16px;
      background-color: blue;
      animation: popIn 0s ease-in;
    }

    @keyframes popIn {
      from {
        transform: scaleX(0);
        opacity: 0.2;
      }
      to {
        transform: scaleX(1);
        opacity: 1;
      }
    }

    .poweredby {
      margin-top: 20px;
      width: 100px;
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const progressBar = document.querySelector(".progress-bar");
      const maxBlocksVisible = 14;
      const resetAfterBlocks = 14;
      let totalBlocks = 0;

      const interval = setInterval(() => {
        if (totalBlocks >= resetAfterBlocks) {
          progressBar.innerHTML = '';
          totalBlocks = 0;
        }

        const newBlock = document.createElement("div");
        newBlock.classList.add("block");
        progressBar.appendChild(newBlock);
        totalBlocks++;

        if (progressBar.children.length > maxBlocksVisible) {
          progressBar.removeChild(progressBar.children[0]);
        }
      }, 50);

      setTimeout(() => {
        clearInterval(interval);
        window.location.href = "orders.php";
      }, 4000);
    });
  </script>
</head>
<body>
  <div class="container">
    <h3>Process Payments</h3>
    <div class="progress-container">
      <div class="progress-bar"></div>
    </div>
    <h4>Redirecting to Orders...</h4>
    <img src="assets/poweredby.png" class="poweredby">
  </div>
</body>
</html>