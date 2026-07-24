import React from 'react';
import { createRoot } from 'react-dom/client';
import AttendanceChart from './components/AttendanceChart';
import DeviceStatusWidget from './components/DeviceStatusWidget';

document.addEventListener('DOMContentLoaded', () => {
    // Mount AttendanceChart
    const chartContainer = document.getElementById('react-attendance-chart');
    if (chartContainer) {
        const rawData = chartContainer.getAttribute('data-chart') || '[]';
        let parsedData = [];
        try {
            parsedData = JSON.parse(rawData);
        } catch (e) {
            console.error('Error parsing chart data', e);
        }
        createRoot(chartContainer).render(<AttendanceChart chartData={parsedData.length ? parsedData : undefined} />);
    }

    // Mount DeviceStatusWidget
    const deviceContainer = document.getElementById('react-device-status');
    if (deviceContainer) {
        const rawDevices = deviceContainer.getAttribute('data-devices') || '[]';
        let parsedDevices = [];
        try {
            parsedDevices = JSON.parse(rawDevices);
        } catch (e) {
            console.error('Error parsing devices data', e);
        }
        createRoot(deviceContainer).render(<DeviceStatusWidget initialDevices={parsedDevices} />);
    }
});
