import React from 'react';
import { createRoot } from 'react-dom/client';
import { createIcons, icons } from 'lucide';
import AttendanceChart from './components/AttendanceChart';
import DeviceStatusWidget from './components/DeviceStatusWidget';

window.lucide = {
    createIcons: (options) => {
        const opts = options || {};
        if (!opts.icons || Object.keys(opts.icons).length === 0) {
            opts.icons = icons;
        }
        return createIcons(opts);
    },
    icons
};

const initIcons = () => {
    try {
        window.lucide.createIcons();
    } catch (e) {
        console.error('Error al inicializar Lucide icons:', e);
    }
};

const mountReactComponents = () => {
    initIcons();

    // Mount AttendanceChart
    const chartContainer = document.getElementById('react-attendance-chart');
    if (chartContainer) {
        if (chartContainer._reactRoot) {
            try {
                chartContainer._reactRoot.unmount();
            } catch (e) {
                console.error('Error unmounting chart root', e);
            }
            delete chartContainer._reactRoot;
        }
        const rawData = chartContainer.getAttribute('data-chart') || '[]';
        let parsedData = [];
        try {
            parsedData = JSON.parse(rawData);
        } catch (e) {
            console.error('Error parsing chart data', e);
        }
        const root = createRoot(chartContainer);
        chartContainer._reactRoot = root;
        root.render(<AttendanceChart chartData={parsedData.length ? parsedData : undefined} />);
    }

    // Mount DeviceStatusWidget
    const deviceContainer = document.getElementById('react-device-status');
    if (deviceContainer) {
        if (deviceContainer._reactRoot) {
            try {
                deviceContainer._reactRoot.unmount();
            } catch (e) {
                console.error('Error unmounting device root', e);
            }
            delete deviceContainer._reactRoot;
        }
        const rawDevices = deviceContainer.getAttribute('data-devices') || '[]';
        let parsedDevices = [];
        try {
            parsedDevices = JSON.parse(rawDevices);
        } catch (e) {
            console.error('Error parsing devices data', e);
        }
        const root = createRoot(deviceContainer);
        deviceContainer._reactRoot = root;
        root.render(<DeviceStatusWidget initialDevices={parsedDevices} />);
    }
};

// Event Listeners for DOMReady and Livewire / Single-Page Navigation
document.addEventListener('DOMContentLoaded', mountReactComponents);
document.addEventListener('livewire:navigated', mountReactComponents);
document.addEventListener('livewire:load', mountReactComponents);
window.addEventListener('popstate', mountReactComponents);
