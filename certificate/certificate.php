<?php
$name = "John Doe";
$course = "Web Development";
$date = date("F j, Y");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <section class="w-full h-fit flex flex-col justify-center items-center">
        <div id="certificate" class="w-[842px] h-[595px] bg-[url('CertificateBG.svg')] flex flex-col justify-between items-center p-[50px] absolute top-[-9999px] left-[-9999px]">
            <!-- Upper Part -->
            <div class="flex flex-col justify-center items-center">
                <h1 class="font-semibold text-[24px] pb-[25px]">Certificate of Completion</h1>
                <div class="max-w-[600px] size-auto flex flex-col items-center gap-[34px] justify-center text-center">
                    <p>This is to certify that</p>
                    <h2 class="font-semibold text-[40px]"><?php echo $name; ?></h2>
                    <p>has successfully completed <span class="font-semibold"><?php echo $course; ?></span> with a grade of <span class="font-semibold">00.00</span></p>
                </div>
            </div>
            <!-- Lower Part -->
            <div class="w-full h-fit flex flex-col justify-center items-center gap-[25px]">
                <div class="w-full h-fit flex justify-around items-center relative">
                    <img src="./Logo.svg" alt="Logo" class="w-[137.2px] h-[67.25px] absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="size-auto text-center flex flex-col">
                        <p class="font-semibold">Omar Eguia</p>
                        <p>Head of Al-Ghaya</p>
                    </div>
                    <div class="size-auto text-center flex flex-col">
                        <p>Certificate Code:</p>
                        <p class="font-semibold">CODE123456</p>
                    </div>
                </div>
                <p>Date: <span><?php echo $date; ?></span></p>
            </div>
        </div>
    </section>
    <button id="downloadButton" class="block mx-auto mt-10 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">Download Certificate</button>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
    <script>
        document.getElementById('downloadButton').addEventListener('click', function() {
            const element = document.getElementById('certificate');

            // Use html2canvas to capture the element
            html2canvas(element, {
                scale: 1 // Matches your original scale
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 1); // Matches your image type and quality

                // Access jsPDF from the global window.jspdf
                const {
                    jsPDF
                } = window.jspdf;

                // Create jsPDF with your original options
                const pdf = new jsPDF({
                    unit: 'in',
                    format: 'a4',
                    orientation: 'landscape'
                });

                // Get page dimensions in inches
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                // Assume 96 DPI for converting pixel dimensions to inches (common for web CSS pixels)
                const imgWidth = canvas.width / 96;
                const imgHeight = canvas.height / 96;

                // Calculate centered position (no scaling, keeps original size)
                const x = (pageWidth - imgWidth) / 2;
                const y = (pageHeight - imgHeight) / 2;

                // Add the image to the PDF at the centered position
                pdf.addImage(imgData, 'JPEG', x, y, imgWidth, imgHeight);

                // Save the PDF
                pdf.save('certificate.pdf');
            });
        });
    </script>
</body>

</html>