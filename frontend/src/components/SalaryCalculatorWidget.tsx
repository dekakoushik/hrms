'use client';

import React, { useState } from 'react';
import { calculateSalaryClient, SalaryBreakdown } from '@/types/employee';
import { IndianRupee, ShieldCheck, Calculator } from 'lucide-react';

interface SalaryCalculatorProps {
  basicSalary: number;
  monthlyTax: number;
  enableEpf: boolean;
  currencySymbol?: string;
  onBasicChange: (val: number) => void;
  onTaxChange: (val: number) => void;
  onEpfToggle: (val: boolean) => void;
}

export default function SalaryCalculatorWidget({
  basicSalary,
  monthlyTax,
  enableEpf,
  currencySymbol = '₹',
  onBasicChange,
  onTaxChange,
  onEpfToggle,
}: SalaryCalculatorProps) {
  const breakdown: SalaryBreakdown = calculateSalaryClient(basicSalary, monthlyTax, enableEpf);

  const formatCurrency = (val: number) => {
    return `${currencySymbol}${val.toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`;
  };

  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
      <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100">
        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100">
          <Calculator className="h-5 w-5" />
        </div>
        <div>
          <h3 className="text-base font-bold text-slate-900">
            Compensation & Statutory Salary Architecture
          </h3>
          <p className="text-xs text-slate-500">
            Real-time automatic breakdown compliant with EPF, ESIC, Gratuity and TDS
          </p>
        </div>
      </div>

      {/* Inputs Bar */}
      <div className="mt-5 grid grid-cols-1 md:grid-cols-3 gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-slate-800">
            Basic Salary (Monthly {currencySymbol}) *
          </label>
          <div className="relative mt-1.5">
            <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-sm font-semibold">
              {currencySymbol}
            </span>
            <input
              type="number"
              min="0"
              step="any"
              required
              value={basicSalary === 0 ? '' : basicSalary}
              onChange={(e) => onBasicChange(parseFloat(e.target.value) || 0)}
              placeholder="e.g. 15000"
              className="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm font-bold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-slate-800">
            Monthly Tax / TDS ({currencySymbol})
          </label>
          <div className="relative mt-1.5">
            <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-sm font-semibold">
              {currencySymbol}
            </span>
            <input
              type="number"
              min="0"
              step="any"
              value={monthlyTax === 0 ? '' : monthlyTax}
              onChange={(e) => onTaxChange(parseFloat(e.target.value) || 0)}
              placeholder="0"
              className="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
            />
          </div>
        </div>

        <div className="flex flex-col justify-center">
          <label className="flex items-center gap-3 cursor-pointer select-none">
            <input
              type="checkbox"
              checked={enableEpf}
              onChange={(e) => onEpfToggle(e.target.checked)}
              className="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
            />
            <div>
              <span className="text-xs font-bold text-slate-900">Enable EPF Deduction (12%)</span>
              <p className="text-[11px] text-slate-500">Capped at max {currencySymbol}3,000 as per rule</p>
            </div>
          </label>
        </div>
      </div>

      {/* Two Column Output Box matching attached spec image */}
      <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-5">
        {/* Left Column: Monthly Earnings & Allowances */}
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100">
              Monthly Earnings & Allowances
            </h4>
            <div className="mt-3.5 space-y-2.5 text-xs text-slate-600">
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Basic Salary:</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.basic_salary)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Dearness Allowance (DA - 20%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.da_amount)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>House Rent Allowance (HRA - 60%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.hra_amount)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Conveyance Allowance (CA - 20%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.ca_amount)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Entertainment Allowance (EA - 8%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.ea_amount)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Special Allowance (SA - 20%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.sa_amount)}
                </span>
              </div>
            </div>
          </div>

          <div className="mt-5 pt-3 border-t border-slate-200 flex justify-between items-center bg-slate-50/70 p-3 rounded-lg">
            <span className="text-sm font-bold text-slate-900">Gross Salary:</span>
            <span className="text-base font-extrabold text-blue-700 font-mono">
              {formatCurrency(breakdown.gross_salary)}
            </span>
          </div>
        </div>

        {/* Right Column: Statutory Benefits, Deductions & Net Pay */}
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100">
              Statutory Benefits, Deductions & Net Pay
            </h4>
            <div className="mt-3.5 space-y-2.5 text-xs text-slate-600">
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Gratuity (Basic+DA * 4.81%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.gratuity_amount)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Employee PF (Max {currencySymbol}3000):</span>
                <span className="font-semibold text-red-600 font-mono">
                  {formatCurrency(breakdown.employee_pf)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>
                  Employee ESIC (0.75%):{' '}
                  <span className="text-[10px] text-slate-400">(Gross &lt;= 21k)</span>
                </span>
                <span className="font-semibold text-red-600 font-mono">
                  {formatCurrency(breakdown.employee_esic)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Employer ESIC (3.25%):</span>
                <span className="font-semibold text-slate-900 font-mono">
                  {formatCurrency(breakdown.employer_esic)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-50">
                <span>Income Tax (TDS):</span>
                <span className="font-semibold text-red-600 font-mono">
                  {formatCurrency(breakdown.monthly_tax)}
                </span>
              </div>
              <div className="flex justify-between items-center py-1 border-b border-slate-100 font-bold text-slate-900">
                <span className="text-red-700">Total Deductions:</span>
                <span className="font-bold text-red-700 font-mono">
                  {formatCurrency(breakdown.total_deductions)}
                </span>
              </div>
            </div>
          </div>

          <div className="mt-5 pt-3 border-t border-slate-200 flex flex-col gap-2">
            <div className="flex justify-between items-center bg-emerald-50/70 p-3 rounded-lg border border-emerald-100">
              <span className="text-xs font-bold text-emerald-900">Net Take-Home Salary:</span>
              <span className="text-base font-extrabold text-emerald-700 font-mono">
                {formatCurrency(breakdown.net_salary)}
              </span>
            </div>
            <div className="flex justify-between items-center bg-blue-50/70 p-3 rounded-lg border border-blue-100">
              <span className="text-xs font-bold text-blue-900">Cost to Company (CTC):</span>
              <span className="text-base font-extrabold text-blue-700 font-mono">
                {formatCurrency(breakdown.ctc_amount)}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

