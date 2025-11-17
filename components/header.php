<!-- Main Header Component -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Main CSS -->
  <link rel="stylesheet" href="../../dist/css/style.css" />
  <!-- Tailwind -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">
    /* Font Styles */
    .sub-header {
      @apply text-[31.1px] font-bold;
    }

    .price-sub-header {
      @apply text-[31.1px] font-semibold text-company_blue;
    }

    .footer-sub-header {
      @apply w-full text-left text-[21.6px] font-semibold;
    }

    .body-text2-semibold {
      @apply text-[21.6px] font-semibold;
    }

    .base-light {
      @apply font-light;
    }

    .section-title {
      @apply text-[31.1px] font-bold text-secondary;
    }

    .program-name-2 {
      @apply text-[31.1px] font-bold;
    }

    .label {
      @apply text-company_grey;
    }

    .menu-item-inactive {
      @apply gap-[5px] font-semibold text-secondary hover:text-company_black hover:cursor-pointer;
    }

    .menu-item-inactive-admin {
      @apply gap-[5px] font-semibold text-company_white hover:text-company_white hover:cursor-pointer;
    }

    .menu-item-active {
      @apply gap-[5px] font-semibold text-tertiary hover:text-tertiary hover:cursor-pointer;
    }

    .menu-item-active-admin {
      @apply gap-[5px] font-semibold text-tertiary hover:text-tertiary hover:cursor-pointer;
    }


    /* Layout Styles */
    .page-container {
      @apply w-full min-h-[calc(100dvh-90px)] flex flex-col items-center justify-start bg-primary overflow-y-auto;
    }

    .page-content {
      @apply max-w-[1200px] w-full h-fit flex flex-col p-[50px];
    }

    .content-section {
      @apply w-full flex flex-col gap-[25px] pb-[75px];
    }

    .section-card {
      @apply w-full h-auto flex bg-company_white rounded-[30px] gap-[50px] p-[50px] overflow-hidden items-center;
    }
   
    .section-card-payment {
      @apply w-full h-auto flex bg-company_white rounded-[30px] gap-[50px] p-[50px] overflow-hidden items-start;
    }

    .section-card-inputs {
      @apply w-full h-auto flex flex-col bg-company_white rounded-[20px] gap-[50px] p-[25px] overflow-hidden items-start;
    }
    
    .quick-access-card {
      @apply w-full h-auto flex bg-company_white rounded-[30px] gap-[20px] p-[20px] overflow-hidden items-center;
    }
    
    .teacher-programs-card {
      @apply w-full h-auto flex flex-col bg-company_white rounded-[40px] gap-[20px] p-[20px] overflow-hidden items-center;
    }

    .input-field {
      @apply w-full h-[40px] bg-[#BBBBBB]/15 border border-[#888888]/10 rounded-[10px] p-[12px] placeholder:text-[#999999] focus:outline-offset-2 focus:accent-tertiary;
    }

    .proficiency-badge {
      @apply w-fit h-fit px-[15px] py-[5px] rounded-tl-[10px] rounded-br-[10px] rounded-tr-[5px] rounded-bl-[5px] gap-x-[5px] flex items-center bg-company_black text-company_white;
    }

    .proficiency-badge-creation {
      @apply w-fit h-fit px-[15px] py-[5px] rounded-tl-[10px] rounded-br-[10px] rounded-tr-[5px] rounded-bl-[5px] gap-x-[5px] flex items-center;
    }


    /* Button Styles */
    .btn-gold {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-tertiary text-company_white hover:bg-company_black transition duration-300 ease-in-out; 
    }

    .btn-green {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-company_green text-company_white hover:bg-company_black transition duration-300 ease-in-out; 
    }

    .btn-orange {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-company_orange text-company_white hover:bg-company_black transition duration-300 ease-in-out; 
    }

    .btn-red {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-company_red text-company_white hover:bg-company_black transition duration-300 ease-in-out; 
    }

    .btn-blue {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-company_blue text-company_white hover:bg-company_black transition duration-300 ease-in-out; 
    }
    
    .btn-grey {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-primary text-company_grey hover:bg-company_black hover:text-company_white transition duration-300 ease-in-out; 
    }

    .btn-blue-inactive {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-primary text-company_grey hover:bg-company_blue/30 hover:text-company_white transition duration-300 ease-in-out; 
    }

    .btn-orange-inactive {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-primary text-company_grey hover:bg-company_orange/30 hover:text-company_white transition duration-300 ease-in-out; 
    }

    .btn-secondary {
      @apply px-[28px] py-[16px] gap-[5px] rounded-[10px] w-fit h-fit cursor-pointer flex items-center bg-secondary text-company_white disabled:opacity-50 disabled:pointer-events-none hover:bg-company_black transition duration-300 ease-in-out; 
    }

    /* Theme Colors */
    @theme {
      --color-primary: #e6e6e8;
      --color-secondary: #10375b;
      --color-tertiary: #a58618;
      --color-company_black: #05051a;
      --color-company_white: #f3f3fc;
      --color-company_grey: #888888;
      --color-company_blue: #2d73c0;
      --color-company_green: #2d8f5f;
      --color-company_red: #bd4542;
      --color-company_orange: #c66e20;
    }
  </style>
  <!-- Phosphor Icons -->
  <script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2"></script>
  <!-- Swiper JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="icon" type="image/x-icon" href="../../images/al-ghaya_logoForPrint.svg">
  <title><?php echo $page_title ?? 'Default Title'; ?> | Al-Ghaya</title>
</head>

<body>