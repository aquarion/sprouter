import sprouter from "../../icons/spouter-standard.png";

export default function AppLogoIcon({
	className,
	...props
}: { className?: string } & React.ImgHTMLAttributes<HTMLImageElement>) {
	return (
		<img
			src={sprouter}
			alt="Sprouter"
			className={className}
			{...props}
		/>
	);
}
