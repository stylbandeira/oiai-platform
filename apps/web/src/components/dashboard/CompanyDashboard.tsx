import { useEffect, useState } from "react";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { CompanyDashboardContent } from "./CompanyDashboardContent";
import { UserCompanyAccessRequest } from "../forms/UserCompanyAccessRequest";

export interface DashboardData {
  totalProducts: number;
  activeWebhooks: number;
  monthlyUpdates: number;
  userEngagement: number;
}

const defaultDashboardData: DashboardData = {
  totalProducts: 0,
  activeWebhooks: 0,
  monthlyUpdates: 0,
  userEngagement: 0,
};

export function CompanyDashboard() {
  const user = useUser();

  const [dashboardData, setDashboardData] =
    useState<DashboardData>(defaultDashboardData);

  const hasCompanies = Boolean(user?.user?.activeCompanies?.length);

  const fetchDashData = async () => {
    const company_id = user?.user?.activeCompanies?.[0]?.id;
    //TODO - PEGAR DA SELEÇÃO
    try {
      const response = await api.get('/companies/' + company_id + '/dashboard');
      setDashboardData(response.data);
    } catch (error) {
      console.error("Erro ao buscar dashboard:", error);
    }
  };

  useEffect(() => {
    if (hasCompanies) {
      fetchDashData();
    }
  }, [hasCompanies]);

  return (
    <div className="container mx-auto px-4 py-6 space-y-6">
      {hasCompanies ? (
        <CompanyDashboardContent dashboardData={dashboardData} />
      ) : (
        <UserCompanyAccessRequest />
      )}
    </div>
  );
}