mod models;
mod solver;

use axum::{
    extract::Json,
    response::{Html, IntoResponse},
    routing::{get, post},
    Router,
};
use models::{HealthResponse, SolveRequest, SolveResponse};
use solver::CloudSolver;
use std::net::SocketAddr;
use tower_http::cors::{Any, CorsLayer};
use tracing_subscriber::{layer::SubscriberExt, util::SubscriberInitExt};

#[tokio::main]
async fn main() {
    tracing_subscriber::registry()
        .with(tracing_subscriber::EnvFilter::try_from_default_env().unwrap_or_else(|_| "info".into()))
        .with(tracing_subscriber::fmt::layer())
        .init();

    let cors = CorsLayer::new()
        .allow_origin(Any)
        .allow_headers(Any)
        .allow_methods(Any);

    let app = Router::new()
        .route("/health", get(health_check))
        .route("/portal", get(portal_landing))
        .route("/api/v1/solve", post(solve_handler))
        .layer(cors);

    let addr = SocketAddr::from(([0, 0, 0, 0], 8080));
    println!("🚀 Academic Timetabler Rust Cloud Solver Engine listening on http://{}", addr);

    let listener = tokio::net::TcpListener::bind(addr).await.unwrap();
    axum::serve(listener, app).await.unwrap();
}

async fn health_check() -> Json<HealthResponse> {
    Json(HealthResponse {
        status: "ok".to_string(),
        service: "Academic Timetabler Cloud Engine".to_string(),
        version: "1.0.0".to_string(),
        engine: "Rust 100% Native High-Performance Solver".to_string(),
    })
}

async fn portal_landing() -> impl IntoResponse {
    Html(r#"
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Academic Timetabler Cloud Solver Portal</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-4">
        <div class="card shadow-lg border-0 rounded-4 max-w-lg p-4 bg-white text-center" style="max-width: 600px;">
            <div class="mb-3 text-primary fs-1">🚀</div>
            <h2 class="fw-bold text-dark mb-2">Cloud Solver Acceleration Portal</h2>
            <p class="text-muted mb-4">You are connected to the high-performance off-server Rust constraint solver engine for Moodle.</p>
            <div class="alert alert-success border-0 rounded-3 p-3 mb-4 text-start">
                <div class="d-flex align-items-center">
                    <span class="fs-4 me-3">⚡</span>
                    <div>
                        <strong class="d-block text-success">100% Rust Multithreaded Engine Active</strong>
                        <small class="text-muted">Processes 10,000+ course schedules off-server with 0ms PHP overhead.</small>
                    </div>
                </div>
            </div>
            <div class="text-start mb-4">
                <h6>Connecting Moodle to Cloud Acceleration:</h6>
                <ol class="small text-muted ps-3">
                    <li>Copy your Cloud Solver API key.</li>
                    <li>Open <strong>Site Administration &rarr; Plugins &rarr; Academic Timetabler Settings</strong> in Moodle.</li>
                    <li>Paste your key into the <strong>Cloud Solver API Key</strong> field and save changes.</li>
                </ol>
            </div>
            <a href="/health" class="btn btn-primary font-weight-bold py-2 w-100 rounded-3">Check Engine Health Endpoint &rarr;</a>
        </div>
    </body>
    </html>
    "#)
}

async fn solve_handler(Json(payload): Json<SolveRequest>) -> Json<SolveResponse> {
    let result = CloudSolver::solve(payload);
    Json(result)
}
