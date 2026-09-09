import { ReactNode } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

interface StandardDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  actions?: ReactNode;
  className?: string;
  bodyClassName?: string;
}

export function StandardDialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  actions,
  className,
  bodyClassName,
}: StandardDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className={cn(
          "flex max-h-[90vh] w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg",
          className,
        )}
      >
        <DialogHeader className="border-b px-6 py-5 pr-12">
          <DialogTitle>{title}</DialogTitle>
          {description && <DialogDescription>{description}</DialogDescription>}
        </DialogHeader>

        <div className={cn("overflow-y-auto px-6 py-5", bodyClassName)}>
          {children}
        </div>

        {actions && (
          <DialogFooter className="border-t bg-muted/30 px-6 py-4">
            {actions}
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
