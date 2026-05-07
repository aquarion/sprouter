import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/connections';
import bluesky from '@/routes/bluesky';
import mastodon from '@/routes/mastodon';

interface SocialConnection {
    provider: 'mastodon' | 'bluesky';
    handle: string;
    instance_url: string | null;
}

export default function Connections({
    connections,
}: {
    connections: SocialConnection[];
}) {
    const mastodonConnection = connections.find((c) => c.provider === 'mastodon');
    const blueskyConnection = connections.find((c) => c.provider === 'bluesky');

    return (
        <>
            <Head title="Connected accounts" />

            <h1 className="sr-only">Connected accounts</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Connected accounts"
                    description="Connect your Mastodon and Bluesky accounts to populate your feed."
                />

                {/* Mastodon */}
                <div className="rounded-lg border p-6">
                    <h3 className="mb-4 text-base font-semibold">Mastodon</h3>
                    {mastodonConnection ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Connected as{' '}
                                <strong>{mastodonConnection.handle}</strong>
                            </p>
                            <Form {...mastodon.destroy.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        Disconnect
                                    </Button>
                                )}
                            </Form>
                        </div>
                    ) : (
                        <Form
                            {...mastodon.redirect.form()}
                            className="space-y-4"
                        >
                            {({ processing }) => (
                                <>
                                    <div className="space-y-1">
                                        <Label htmlFor="instance_url">
                                            Instance URL
                                        </Label>
                                        <Input
                                            id="instance_url"
                                            name="instance_url"
                                            placeholder="https://mastodon.social"
                                        />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        Connect Mastodon
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </div>

                {/* Bluesky */}
                <div className="rounded-lg border p-6">
                    <h3 className="mb-4 text-base font-semibold">Bluesky</h3>
                    {blueskyConnection ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Connected as{' '}
                                <strong>{blueskyConnection.handle}</strong>
                            </p>
                            <Form {...bluesky.destroy.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        Disconnect
                                    </Button>
                                )}
                            </Form>
                        </div>
                    ) : (
                        <Form
                            {...bluesky.store.form()}
                            className="space-y-4"
                        >
                            {({ processing }) => (
                                <>
                                    <div className="space-y-1">
                                        <Label htmlFor="bsky_handle">
                                            Handle
                                        </Label>
                                        <Input
                                            id="bsky_handle"
                                            name="handle"
                                            placeholder="alice.bsky.social"
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="app_password">
                                            App Password
                                        </Label>
                                        <Input
                                            id="app_password"
                                            name="app_password"
                                            type="password"
                                            placeholder="xxxx-xxxx-xxxx-xxxx"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Generate one at Settings &rarr;
                                            Privacy and Security &rarr; App
                                            Passwords in Bluesky.
                                        </p>
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        Connect Bluesky
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </div>
            </div>
        </>
    );
}

Connections.layout = {
    breadcrumbs: [
        {
            title: 'Connected accounts',
            href: edit(),
        },
    ],
};
