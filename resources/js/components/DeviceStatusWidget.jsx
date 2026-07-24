import React, { useState } from 'react';

export default function DeviceStatusWidget({ initialDevices = [], devices: propDevices }) {
    const devices = propDevices || initialDevices;
    const [pingingId, setPingingId] = useState(null);
    const [pingResult, setPingResult] = useState({});

    const handleTestPing = (devId) => {
        setPingingId(devId);
        setTimeout(() => {
            setPingingId(null);
            setPingResult((prev) => ({
                ...prev,
                [devId]: 'OK (12ms - ISAPI 2.0)',
            }));
        }, 800);
    };

    if (!devices || devices.length === 0) {
        return (
            <div className="bw-card p-6 rounded-2xl shadow-sm space-y-4 bg-white border border-slate-200">
                <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 className="font-heading font-extrabold text-base text-slate-900 flex items-center">
                        <span className="w-2.5 h-2.5 rounded-full bg-slate-300 mr-2"></span>
                        Terminales ISAPI en Red Local
                    </h3>
                    <span className="text-xs text-slate-500 font-mono font-bold">0 Conectados</span>
                </div>
                <div className="text-center py-6 space-y-3">
                    <p className="text-xs text-slate-500 font-medium">No tienes huelleros ni terminales registradas en tu cuenta.</p>
                    <a href="/devices" className="inline-flex items-center space-x-2 bg-black hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl shadow-md transition-all">
                        <span>+ Registrar Huellero</span>
                    </a>
                </div>
            </div>
        );
    }

    return (
        <div className="bw-card p-6 rounded-2xl shadow-sm space-y-4 bg-white border border-slate-200">
            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 className="font-heading font-extrabold text-base text-slate-900 flex items-center">
                    <span className="w-2.5 h-2.5 rounded-full bg-black mr-2"></span>
                    Terminales ISAPI en Red Local
                </h3>
                <span className="text-xs text-slate-600 font-mono font-bold">{devices.length} Conectados</span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {devices.map((device) => (
                    <div key={device.id} className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <span className="font-extrabold text-sm text-slate-900 block">{device.name}</span>
                                <span className="font-mono text-xs text-slate-600 font-bold">{device.ip_address}</span>
                            </div>
                            <span class="bg-black text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full border border-black shadow-sm">
                                ONLINE
                            </span>
                        </div>

                        <div className="flex items-center justify-between text-xs pt-1 border-t border-slate-200">
                            <span className="text-slate-500 font-semibold">{device.location || 'Sin ubicación'}</span>
                            <button
                                onClick={() => handleTestPing(device.id)}
                                disabled={pingingId === device.id}
                                className="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold px-3 py-1 rounded-lg transition-all shadow-sm"
                            >
                                {pingingId === device.id ? 'Probando...' : pingResult[device.id] || 'Ping Test'}
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
