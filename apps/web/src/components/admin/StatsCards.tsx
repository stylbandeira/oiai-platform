// src/components/admin/StatsCards.tsx
import { Card, CardContent } from "@/components/ui/card";

interface Stat {
    label: string;
    value: number | string;
    icon: React.ReactNode;
    color: string;
}

interface StatsCardsProps {
    stats: Stat[];
}

export function StatsCards({ stats }: StatsCardsProps) {
    return (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
            {stats.map((stat, index) => (
                <Card key={index} className="border-0 shadow-sm bg-white">
                    <CardContent className="p-3 sm:p-6">
                        <div className="flex items-center gap-2 sm:gap-3">
                            <div className={`w-8 h-8 sm:w-12 sm:h-12 ${stat.color} rounded-lg flex items-center justify-center flex-shrink-0`}>
                                {stat.icon}
                            </div>
                            <div className="min-w-0">
                                <p className="text-base sm:text-2xl font-bold truncate">{stat.value}</p>
                                <p className="text-[10px] sm:text-sm text-muted-foreground truncate">{stat.label}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}