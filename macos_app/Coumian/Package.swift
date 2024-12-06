// swift-tools-version:5.5
import PackageDescription

let package = Package(
    name: "Coumian",
    platforms: [
        .macOS(.v11)
    ],
    products: [
        .executable(name: "Coumian", targets: ["Coumian"])
    ],
    dependencies: [
        .package(url: "https://github.com/daltoniam/Starscream.git", from: "4.0.0")
    ],
    targets: [
        .executableTarget(
            name: "Coumian",
            dependencies: ["Starscream"],
            resources: [
                .process("Info.plist")
            ]
        ),
        .testTarget(
            name: "CoumianTests",
            dependencies: ["Coumian"]
        )
    ]
)
