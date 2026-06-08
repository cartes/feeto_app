<template>
    <div class="report-print-surface space-y-8">
        <slot />
    </div>
</template>

<style>
@media print {
  aside,
  header,
  nav,
  .print-hidden {
    display: none !important;
  }

  body,
  main,
  .min-h-screen,
  .report-print-surface {
    background: #fff !important;
  }

  main {
    padding: 0 !important;
    margin: 0 !important;
  }

  .report-print-surface {
    width: 100% !important;
    max-width: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .report-print-surface * {
    box-shadow: none !important;
  }

  .report-print-surface .rounded-\[2rem\],
  .report-print-surface .rounded-2xl,
  .report-print-surface .rounded-full {
    border-radius: 0 !important;
  }
}
</style>
