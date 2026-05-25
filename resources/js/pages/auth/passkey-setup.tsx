import { Head, router } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function PasskeySetup() {
    return (
        <>
            <Head title="Set up a passkey" />

            <div className="flex flex-col items-center gap-6 text-center">
                <KeyRound className="h-12 w-12 text-primary" />

                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">Set up a passkey</h1>
                    <p className="text-sm text-muted-foreground max-w-sm">
                        Passkeys let you sign in with your fingerprint, face, or device PIN — no
                        password needed next time.
                    </p>
                </div>

                <div className="flex flex-col gap-3 w-full max-w-xs">
                    <Button
                        variant="ghost"
                        onClick={() => router.post(route('passkey.setup.skip'))}
                    >
                        Skip for now
                    </Button>
                </div>
            </div>
        </>
    );
}

PasskeySetup.layout = {
    title: 'One more step',
    description: 'Add a passkey for faster, more secure sign-in',
};
