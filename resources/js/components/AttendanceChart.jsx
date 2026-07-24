import React, { useState } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

const emptyData = [
    { day: 'Lun', Puntuales: 0, Tardanzas: 0 },
    { day: 'Mar', Puntuales: 0, Tardanzas: 0 },
    { day: 'Mié', Puntuales: 0, Tardanzas: 0 },
    { day: 'Jue', Puntuales: 0, Tardanzas: 0 },
    { day: 'Vie', Puntuales: 0, Tardanzas: 0 },
    { day: 'Sáb', Puntuales: 0, Tardanzas: 0 },
    { day: 'Dom', Puntuales: 0, Tardanzas: 0 },
];

export default function AttendanceChart({ chartData = emptyData }) {
    const [activeTab, setActiveTab] = useState('semana');
    const dataToDisplay = (chartData && chartData.length > 0) ? chartData : emptyData;

    return (
        <div className="bw-card p-6 rounded-2xl shadow-sm space-y-4 bg-white border border-slate-200">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h3 className="font-heading font-extrabold text-base text-slate-900 flex items-center">
                        <span className="w-2.5 h-2.5 rounded-full bg-black mr-2"></span>
                        Tendencia de Asistencia y Puntualidad
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">Análisis gráfico de marcaciones semanales</p>
                </div>

                <div className="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button
                        onClick={() => setActiveTab('semana')}
                        className={`px-3 py-1.5 rounded-lg text-xs font-extrabold transition-all ${
                            activeTab === 'semana'
                                ? 'bg-black text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        Esta Semana
                    </button>
                    <button
                        onClick={() => setActiveTab('mes')}
                        className={`px-3 py-1.5 rounded-lg text-xs font-extrabold transition-all ${
                            activeTab === 'mes'
                                ? 'bg-black text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        Este Mes
                    </button>
                </div>
            </div>

            {/* Recharts Area Chart Container */}
            <div className="h-72 w-full pt-2">
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={dataToDisplay} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                        <defs>
                            <linearGradient id="colorPuntuales" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#000000" stopOpacity={0.25}/>
                                <stop offset="95%" stopColor="#000000" stopOpacity={0}/>
                            </linearGradient>
                            <linearGradient id="colorTardanzas" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#64748b" stopOpacity={0.25}/>
                                <stop offset="95%" stopColor="#64748b" stopOpacity={0}/>
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                        <XAxis dataKey="day" stroke="#64748b" tick={{ fill: '#475569', fontSize: 12, fontWeight: 600 }} />
                        <YAxis stroke="#64748b" tick={{ fill: '#475569', fontSize: 12, fontWeight: 600 }} />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: '#ffffff',
                                borderColor: '#cbd5e1',
                                borderRadius: '12px',
                                boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                                color: '#0f172a',
                                fontSize: '12px',
                                fontWeight: '600'
                            }}
                        />
                        <Legend wrapperStyle={{ paddingTop: '10px', fontSize: '12px', fontWeight: '700' }} />
                        <Area type="monotone" dataKey="Puntuales" stroke="#000000" strokeWidth={2.5} fillOpacity={1} fill="url(#colorPuntuales)" />
                        <Area type="monotone" dataKey="Tardanzas" stroke="#64748b" strokeWidth={2.5} fillOpacity={1} fill="url(#colorTardanzas)" />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
