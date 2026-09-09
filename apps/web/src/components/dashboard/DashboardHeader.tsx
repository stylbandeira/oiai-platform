import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { ShoppingCart, Building2, Shield, LogOut, Star, Bell, Menu, X } from "lucide-react";
import { CustomLogo } from "../oiai_ui/CustomLogo";
import NotificationsBell from "../notification/NotificationBell";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { useState } from "react";
import { User, UserType } from "@/types/user";

interface DashboardHeaderProps {
  userType: UserType;
  userName: string;
  userPoints?: number;
  user?: User;
  onLogout: () => void;
}

export function DashboardHeader({ userType, userName, userPoints, user, onLogout }: DashboardHeaderProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const getUserTypeInfo = (type: UserType) => {
    switch (type) {
      case "client":
        return {
          icon: <ShoppingCart className="w-4 h-4" />,
          label: "Cliente",
          color: "bg-primary"
        };
      case "company":
        return {
          icon: <Building2 className="w-4 h-4" />,
          label: "Empresa",
          color: "bg-secondary"
        };
      case "admin":
        return {
          icon: <Shield className="w-4 h-4" />,
          label: "Administrador",
          color: "bg-destructive"
        };
    }
  };

  const typeInfo = getUserTypeInfo(userType);

  // Componente do conteúdo do menu mobile
  const MobileMenuContent = () => (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between p-4 border-b">
        <div className="flex items-center gap-2">
          <CustomLogo className="w-8 h-8" />
          <span className="font-bold text-lg">Oiaí</span>
        </div>
        <Button
          variant="ghost"
          size="sm"
          onClick={() => setIsMobileMenuOpen(false)}
          className="h-8 w-8 p-0"
        >
          <X className="h-4 w-4" />
        </Button>
      </div>

      <div className="flex-1 p-4 space-y-4">
        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
          <Avatar className="h-12 w-12">
            <AvatarFallback className={`${typeInfo.color} text-white text-lg`}>
              {userName.charAt(0).toUpperCase()}
            </AvatarFallback>
          </Avatar>
          <div>
            <p className="font-medium">{userName}</p>
            <Badge variant="secondary" className="text-xs mt-1">
              {typeInfo.icon}
              <span className="ml-1">{typeInfo.label}</span>
            </Badge>
          </div>
        </div>

        {userType === "client" && userPoints !== undefined && (
          <div className="flex items-center justify-between p-3 bg-gradient-to-r from-primary/10 to-secondary/10 rounded-lg">
            <div className="flex items-center gap-2">
              <Star className="w-5 h-5 text-secondary" />
              <span className="font-medium">Seus pontos</span>
            </div>
            <span className="font-bold text-lg">{userPoints}</span>
          </div>
        )}

        {/* Exibição do tipo de usuário no menu mobile */}
        <div className="p-3 bg-muted/50 rounded-lg">
          <p className="text-xs text-muted-foreground mb-1">Tipo de conta</p>
          <div className="flex items-center gap-2">
            {typeInfo.icon}
            <span className="font-medium">{typeInfo.label}</span>
          </div>
        </div>

        <Button
          variant="outline"
          className="w-full justify-start gap-2"
          onClick={() => {
            onLogout();
            setIsMobileMenuOpen(false);
          }}
        >
          <LogOut className="w-4 h-4" />
          Sair da conta
        </Button>
      </div>
    </div>
  );

  return (
    <>
      <header className="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b">
        <div className="container px-4 py-3 flex items-center justify-between">
          {/* Logo e título - versão mobile simplificada */}
          <div className="flex items-center gap-2">
            <div className="w-10 h-10 bg-gradient-primary rounded-full flex items-center justify-center shadow-sm">
              <a href="/">
                <CustomLogo className="w-6 h-6" />
              </a>
            </div>
            <div className="hidden min-[480px]:block">
              <h1 className="text-lg font-bold leading-tight">Oiaí</h1>
              <p className="text-xs text-muted-foreground">Pesquisa de Preços</p>
            </div>
          </div>

          {/* Badge do tipo de usuário - visível em desktop */}
          <div className="hidden md:flex items-center gap-2 bg-muted/50 px-3 py-1.5 rounded-full">
            {typeInfo.icon}
            <span className="text-xs font-medium">{typeInfo.label}</span>
          </div>

          {/* Ações principais */}
          <div className="flex items-center gap-2">
            {userType === "client" && userPoints !== undefined && (
              <div className="hidden sm:flex items-center gap-1.5 bg-muted px-2.5 py-1.5 rounded-full">
                <Star className="w-3.5 h-3.5 text-secondary" />
                <span className="font-semibold text-sm">{userPoints}</span>
              </div>
            )}

            <NotificationsBell
              notifications={user.notificationList || []}
              unreadCount={user?.notifications?.length || 0}
            />

            {/* Avatar com tooltip indicando tipo */}
            <div className="relative group">
              <Avatar className="h-8 w-8 ring-2 ring-primary/10 cursor-pointer">
                <AvatarFallback className={`${typeInfo.color} text-white text-sm`}>
                  {userName.charAt(0).toUpperCase()}
                </AvatarFallback>
              </Avatar>
              {/* Tooltip customizado para mobile/desktop */}
              <div className="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                {typeInfo.label}
              </div>
            </div>

            {/* Menu mobile button */}
            <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
              <SheetTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 md:hidden">
                  <Menu className="h-4 w-4" />
                </Button>
              </SheetTrigger>
              <SheetContent side="right" className="w-[280px] p-0">
                <MobileMenuContent />
              </SheetContent>
            </Sheet>

            {/* Botão logout - escondido em mobile, aparece no menu */}
            <Button
              variant="outline"
              size="sm"
              onClick={onLogout}
              className="hidden md:flex items-center gap-2"
            >
              <LogOut className="w-4 h-4" />
              <span>Sair</span>
            </Button>
          </div>
        </div>

        {/* Barra de pontos e tipo mobile - visível apenas em telas pequenas */}
        <div className="md:hidden px-4 py-2 bg-muted/30 border-t flex items-center justify-between">
          {userType === "client" && userPoints !== undefined && (
            <div className="flex items-center gap-1.5">
              <Star className="w-3.5 h-3.5 text-secondary" />
              <span className="text-xs text-muted-foreground">{userPoints} pontos</span>
            </div>
          )}
          <div className="flex items-center gap-1.5 ml-auto">
            {typeInfo.icon}
            <span className="text-xs text-muted-foreground">{typeInfo.label}</span>
          </div>
        </div>
      </header>
    </>
  );
}