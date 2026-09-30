import { createContext, useContext, type ReactNode } from "react";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { useUser } from "@/contexts/UserContext";
import type { Unity } from "@/lib/unityConversions";

interface UnityPage {
  data: Unity[];
  meta: { last_page: number };
}

const UnityCatalogContext = createContext<Unity[]>([]);

async function fetchUnities(): Promise<Unity[]> {
  const unities: Unity[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await api.get<UnityPage>("/unities", { params: { page, per_page: 100 } });
    unities.push(...response.data.data);
    lastPage = response.data.meta.last_page;
    page += 1;
  } while (page <= lastPage);

  return unities;
}

export function UnityCatalogProvider({ children }: { children: ReactNode }) {
  const { user } = useUser();
  const { data } = useQuery({
    queryKey: ["unity-catalog", user?.id],
    queryFn: fetchUnities,
    enabled: Boolean(user),
    staleTime: 5 * 60 * 1000,
  });

  return <UnityCatalogContext.Provider value={data ?? []}>{children}</UnityCatalogContext.Provider>;
}

export function useUnityCatalog(): Unity[] {
  return useContext(UnityCatalogContext);
}
