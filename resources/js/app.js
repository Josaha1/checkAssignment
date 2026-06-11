import './bootstrap';
import { initFlowbite } from 'flowbite';
import ApexCharts from 'apexcharts';

// ใช้กับ Alpine x-init ในหน้า dashboard
window.ApexCharts = ApexCharts;

// re-init flowbite component หลัง Livewire เปลี่ยนหน้า/อัปเดต DOM
document.addEventListener('livewire:navigated', () => initFlowbite());
document.addEventListener('DOMContentLoaded', () => initFlowbite());
